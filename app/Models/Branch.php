<?php

namespace App\Models;

use App\Modules\Purchase\Models\PurchaseRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'address',
        'phone',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function purchaseRequests()
    {
        return $this->hasMany(PurchaseRequest::class, 'branch_id');
    }
}
