<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Support\Collection;
use App\Events\ProductChecked;
use App\Models\DecisionRule;
use App\Models\Comment;

#[Fillable(['check_session_id', 'scan_config_id', 'product_id', 'barcode', 'quantity', 'location_id', 'checked_by', 'checked_at', 'result_status', 'remark'])]
class ProductCheck extends Model
{
    use SoftDeletes;

    public static function syncStatusesForProduct(?int $checkSessionId, ?int $productId): void
    {
        if (! $checkSessionId || ! $productId) {
            return;
        }

        $product = Product::find($productId);
        if (! $product) {
            return;
        }

        $checks = static::where('check_session_id', $checkSessionId)
            ->where('product_id', $productId)
            ->get();

        if ($checks->isEmpty()) {
            return;
        }

        $totalQty = $checks->sum('quantity');
        $closingStock = (int) $product->quantity;
        $isQuantityMatched = ($totalQty === $closingStock);

        foreach ($checks as $check) {
            if ($product->created_during_pickup) {
                if ($check->result_status !== 'UNMATCHED') {
                    $check->update(['result_status' => 'UNMATCHED']);
                }
                continue;
            }

            $baseStatus = 'PASS';

            // Check if there are any failed values recorded for this check
            $hasFailedValues = ProductCheckValue::where('product_check_id', $check->id)
                ->where('status', 'FAIL')
                ->exists();

            if ($hasFailedValues) {
                $baseStatus = 'FAIL';
            } else if ($check->scan_config_id) {
                $scanConfig = ScanConfig::find($check->scan_config_id);
                if ($scanConfig) {
                    $actualValuesMap = ProductCheckValue::where('product_check_id', $check->id)
                        ->pluck('actual_value', 'field_name')
                        ->toArray();

                    $hasEmptyCompareFields = false;
                    foreach (data_get($scanConfig->config_json, 'fields', []) as $fieldConfig) {
                        if (data_get($fieldConfig, 'compare', false)) {
                            $fName = $fieldConfig['field'] ?? null;
                            if ($fName && (!isset($actualValuesMap[$fName]) || $actualValuesMap[$fName] === null || $actualValuesMap[$fName] === '')) {
                                $hasEmptyCompareFields = true;
                                break;
                            }
                        }
                    }
                    if ($hasEmptyCompareFields) {
                        $baseStatus = 'PENDING';
                    }
                }
            }

            $newStatus = ($baseStatus === 'FAIL') ? 'FAIL' : ($isQuantityMatched ? $baseStatus : 'FAIL');

            if ($check->result_status !== $newStatus) {
                $check->update(['result_status' => $newStatus]);
                event(new ProductChecked($check));
            }
        }
    }

    protected static function booted(): void
    {
        static::deleted(function (ProductCheck $check): void {
            $check->decisions()->delete();
        });
    }
    protected function casts(): array
    {
        return [
            'checked_at' => 'datetime',
            'quantity' => 'integer',
        ];
    }

    public function checkSession()
    {
        return $this->belongsTo(CheckSession::class);
    }

    public function scanConfig()
    {
        return $this->belongsTo(ScanConfig::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getClosingStockAttribute(): int
    {
        return (int) ($this->product?->quantity ?? 0);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function checkedBy()
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function checkValues()
    {
        return $this->hasMany(ProductCheckValue::class);
    }

    public function decisions()
    {
        return $this->hasMany(Decision::class);
    }

    public function comments()
    {
        return $this->hasManyThrough(Comment::class, Decision::class, 'product_check_id', 'decision_id');
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function failedCheckValues(): Collection
    {
        return $this->checkValues()
            ->where('status', '!=', 'PASS')
            ->orderBy('field_name')
            ->get();
    }

    public function matchedDecisionRules(): Collection
    {
        $failedFieldNames = $this->failedCheckValues()
            ->pluck('field_name')
            ->filter()
            ->map(fn ($value) => strtolower((string) $value))
            ->values();

        if ($this->result_status === 'UNMATCHED') {
            $failedFieldNames->push('result_status');
        }

        if ($failedFieldNames->isEmpty()) {
            return collect();
        }

        return DecisionRule::with('decisionType')
            ->where('is_active', true)
            ->get()
            ->filter(function (DecisionRule $rule) use ($failedFieldNames): bool {
                return $failedFieldNames->contains(strtolower((string) $rule->criteria_field));
            })
            ->values();
    }

    public function latestDecisionStatus(): ?string
    {
        return $this->decisions()
            ->latest('updated_at')
            ->value('action_status');
    }

    public function solutionStatus(): string
    {
        return match ($this->latestDecisionStatus()) {
            'DONE' => 'Resolved',
            'REJECTED' => 'Dismissed',
            'IN_PROGRESS' => 'In Progress',
            'OPEN' => 'Open',
            default => $this->result_status === 'PASS' ? 'No Action Needed' : 'Pending Review',
        };
    }

    public function reviewDecisionComments(): Collection
    {
        return $this->comments()
            ->with(['user', 'decision.decisionType'])
            ->latest()
            ->get()
            ->map(function (Comment $comment): array {
                return [
                    'decision' => $comment->decision?->decisionType?->name ?? 'Decision',
                    'user' => $comment->user?->name ?? 'Unknown',
                    'type' => $comment->comment_type,
                    'comment' => $comment->comment,
                    'created_at' => optional($comment->created_at)->toDateTimeString(),
                ];
            });
    }
}
