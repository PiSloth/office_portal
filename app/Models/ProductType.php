<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'code', 'is_active'])]
class ProductType extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function productTypeFields()
    {
        return $this->hasMany(ProductTypeField::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function scanConfigs()
    {
        return $this->hasMany(ScanConfig::class);
    }
}
