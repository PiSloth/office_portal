<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['product_type_id', 'name', 'description', 'config_json', 'is_active'])]
class ScanConfig extends Model
{
    protected function casts(): array
    {
        return [
            'config_json' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }
}
