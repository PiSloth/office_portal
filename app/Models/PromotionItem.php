<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PromotionItem extends Model
{
    protected $fillable = [
        'barcode',
        'product',
        'weight',
        'gold_weight',
        'dg_weight',
        'addition_wastage',
        'total_wastage',
        'labor_fee',
        'internal_reference',
        'lot_serial_number',
        'product_subgroup_name',
        'branch_id',
    ];

    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    protected $casts = [
        'weight' => 'decimal:8',
        'gold_weight' => 'decimal:8',
        'dg_weight' => 'decimal:8',
        'addition_wastage' => 'decimal:8',
        'total_wastage' => 'decimal:8',
        'labor_fee' => 'decimal:2',
    ];

    /**
     * Scope query to search by barcode or lot/serial number.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $term = trim($term);

        return $query->where(function (Builder $q) use ($term) {
            $q->where('barcode', 'like', "%{$term}%")
              ->orWhere('lot_serial_number', 'like', "%{$term}%");
        });
    }

    /**
     * Relationship to sold record if already used/sold.
     */
    public function soldItem(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PromotionSoldItem::class, 'promotion_item_id');
    }
}
