<?php

namespace App\Modules\Purchase\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequestPrintLog extends Model
{
    protected $fillable = [
        'purchase_request_id',
        'user_id',
        'printed_at',
    ];

    protected $casts = [
        'printed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class);
    }
}
