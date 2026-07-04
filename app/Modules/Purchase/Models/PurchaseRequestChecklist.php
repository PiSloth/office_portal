<?php

namespace App\Modules\Purchase\Models;

use App\Models\User;
use App\Modules\Core\Workflow\Models\ChecklistItem;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequestChecklist extends Model
{
    protected $fillable = ['purchase_request_id', 'checklist_item_id', 'is_checked', 'user_id'];

    protected $casts = [
        'is_checked' => 'boolean',
    ];

    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function checklistItem()
    {
        return $this->belongsTo(ChecklistItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
