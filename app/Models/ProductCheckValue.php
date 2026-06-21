<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['product_check_id', 'field_name', 'expected_value', 'actual_value', 'difference_value', 'status'])]
class ProductCheckValue extends Model
{
    public function productCheck()
    {
        return $this->belongsTo(ProductCheck::class);
    }
}
