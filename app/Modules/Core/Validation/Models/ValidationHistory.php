<?php

namespace App\Modules\Core\Validation\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ValidationHistory extends Model
{
    protected $fillable = [
        'validatable_type',
        'validatable_id',
        'rule_id', 
        'status', 
        'input_value', 
        'expected_value', 
        'remarks',
        'user_id'
    ];

    protected static function booted()
    {
        static::saved(function (ValidationHistory $history) {
            if ($history->status === 'FAIL') {
                $purchaseRequestId = null;
                $validatable = $history->validatable;
                if ($validatable) {
                    if (get_class($validatable) === \App\Modules\Purchase\Models\PurchaseRequest::class) {
                        $purchaseRequestId = $validatable->id;
                    } elseif (get_class($validatable) === \App\Modules\Purchase\Models\PurchaseItem::class) {
                        $purchaseRequestId = $validatable->purchase_request_id;
                    }
                }

                if ($purchaseRequestId) {
                    $rule = $history->rule;
                    $fieldName = $rule ? ($rule->field_name ?: $rule->label) : 'unknown_field';
                    
                    \App\Modules\Purchase\Models\FailCheck::updateOrCreate([
                        'purchase_request_id' => $purchaseRequestId,
                        'field_name' => $fieldName,
                    ], [
                        'expected_value' => $history->expected_value,
                        'actual_value' => $history->input_value,
                        'remark' => $history->remarks,
                        'user_id' => $history->user_id ?? auth()->id(),
                    ]);

                    if ($rule && $rule->ruleSet && $rule->ruleSet->is_push_decision) {
                        \App\Modules\Purchase\Models\PurchaseDecision::firstOrCreate([
                            'purchase_request_id' => $purchaseRequestId,
                            'status' => 'open',
                        ]);
                    }
                }
            } elseif ($history->status === 'PASS') {
                $purchaseRequestId = null;
                $validatable = $history->validatable;
                if ($validatable) {
                    if (get_class($validatable) === \App\Modules\Purchase\Models\PurchaseRequest::class) {
                        $purchaseRequestId = $validatable->id;
                    } elseif (get_class($validatable) === \App\Modules\Purchase\Models\PurchaseItem::class) {
                        $purchaseRequestId = $validatable->purchase_request_id;
                    }
                }

                if ($purchaseRequestId) {
                    $rule = $history->rule;
                    $fieldName = $rule ? ($rule->field_name ?: $rule->label) : 'unknown_field';

                    // For this specific purchase request only, clear the active fail check
                    \App\Modules\Purchase\Models\FailCheck::where('purchase_request_id', $purchaseRequestId)
                        ->where(function ($q) use ($rule, $fieldName) {
                            $q->where('field_name', $fieldName);
                            if ($rule && $rule->label) {
                                $q->orWhere('field_name', $rule->label);
                            }
                            if ($rule && $rule->field_name) {
                                $q->orWhere('field_name', $rule->field_name);
                            }
                        })
                        ->delete();

                    $remainingFails = \App\Modules\Purchase\Models\FailCheck::where('purchase_request_id', $purchaseRequestId)->count();
                    if ($remainingFails === 0) {
                        \App\Modules\Purchase\Models\PurchaseDecision::where('purchase_request_id', $purchaseRequestId)
                            ->where('status', 'open')
                            ->update(['status' => 'closed']);
                    }
                }
            }
        });
    }

    public function validatable()
    {
        return $this->morphTo();
    }

    public function rule()
    {
        return $this->belongsTo(ValidationRule::class, 'rule_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
