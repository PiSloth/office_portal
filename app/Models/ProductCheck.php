<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['check_session_id', 'product_id', 'checked_by', 'checked_at', 'result_status', 'remark'])]
class ProductCheck extends Model
{
    protected function casts(): array
    {
        return [
            'checked_at' => 'datetime',
        ];
    }

    public function checkSession()
    {
        return $this->belongsTo(CheckSession::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
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

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
