<?php

namespace App\Modules\Purchase\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FailCheck extends Model
{
    protected $fillable = [
        'purchase_request_id',
        'field_name',
        'expected_value',
        'actual_value',
        'remark',
        'user_id'
    ];

    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function whoChecked()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
