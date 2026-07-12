<?php

namespace App\Modules\Purchase\Models;

use App\Models\Branch;
use App\Models\User;
use App\Models\ProductType;
use App\Modules\Core\Workflow\Models\WorkflowState;
use App\Modules\Core\Discussion\Models\DiscussionThread;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id', 
        'product_type_id',
        'user_id', 
        'workflow_state_id', 
        'customer_name', 
        'customer_phone', 
        'customer_address', 
        'customer_nrc',
        'customer_nrc_photo',
        'total_amount', 
        'submitted_at'
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'customer_nrc_photo' => 'array',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function statusUpdater()
    {
        return $this->belongsTo(User::class, 'status_updated_by_id');
    }

    public function workflowState()
    {
        return $this->belongsTo(WorkflowState::class);
    }

    public function productType()
    {
        return $this->belongsTo(ProductType::class, 'product_type_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function purchaseDecision()
    {
        return $this->hasOne(PurchaseDecision::class);
    }

    public function failChecks()
    {
        return $this->hasMany(FailCheck::class);
    }

    public function printLogs()
    {
        return $this->hasMany(PurchaseRequestPrintLog::class);
    }

    public function discussionThreads()
    {
        return $this->morphMany(DiscussionThread::class, 'threadable');
    }

    public function getPurchaseNumberAttribute()
    {
        $branchCode = $this->branch?->code ?: 'MAIN';

        if (!$this->created_at) {
            return 'PR-' . $branchCode . '/Draft';
        }
        
        $date = $this->created_at;
        $seq = \App\Modules\Purchase\Models\PurchaseRequest::whereDate('created_at', $date->toDateString())
            ->where('id', '<=', $this->id)
            ->count();
            
        return 'PR-' . $branchCode . '/' . $date->format('ymd') . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }

    public function updateTotalAmount(): void
    {
        $total = $this->items()->sum('calculated_price');
        $this->updateQuietly(['total_amount' => $total]);
    }
}
