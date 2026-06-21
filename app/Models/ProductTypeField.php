<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['product_type_id', 'field_name', 'field_label', 'field_type', 'required', 'is_active'])]
class ProductTypeField extends Model
{
    protected function casts(): array
    {
        return [
            'required' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }
}
