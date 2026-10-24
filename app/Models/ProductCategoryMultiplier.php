<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategoryMultiplier extends Model
{
    use HasFactory;

    protected $table = 'product_category_multipliers';

    protected $fillable = [
        'name',
        'multiplier',
        'description',
    ];

    protected $casts = [
        'multiplier' => 'decimal:16',
    ];

    public function promotionItems(): HasMany
    {
        return $this->hasMany(PromotionItem::class, 'product_category_id');
    }
}
