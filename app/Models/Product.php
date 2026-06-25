<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'product_type_id',
    'location_id',
    'category_id',
    'sub_category_id',
    'code',
    'barcode',
    'qr_code',
    'name',
    'description',
    'quantity',
    'status',
    'import_batch_id',
    'created_during_pickup'
])]
class Product extends Model
{
    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class);
    }

    public function attributeValues()
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    public function importBatch()
    {
        return $this->belongsTo(ProductImportBatch::class, 'import_batch_id');
    }

    public function productChecks()
    {
        return $this->hasMany(ProductCheck::class);
    }

    /**
     * Helper to get a dynamic attribute value
     */
    public function getDynamicAttributeValue(string $fieldName): ?string
    {
        return $this->attributeValues()->where('field_name', $fieldName)->first()?->value;
    }
}
