<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['product_type_id', 'field_name', 'field_label', 'field_type', 'required', 'show_in_creation_form', 'show_in_table_by_default', 'is_active'])]
class ProductTypeField extends Model
{
    protected function casts(): array
    {
        return [
            'required' => 'boolean',
            'show_in_creation_form' => 'boolean',
            'show_in_table_by_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }
}
