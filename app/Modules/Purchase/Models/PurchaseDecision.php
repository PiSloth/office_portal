<?php

namespace App\Modules\Purchase\Models;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Model;

class PurchaseDecision extends Model
{
    protected $fillable = [
        'purchase_request_id',
        'status',
        'remark'
    ];

    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
