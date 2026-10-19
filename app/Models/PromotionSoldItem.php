<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionSoldItem extends Model
{
    protected $fillable = [
        'promotion_item_id',
        'user_id',
        'promotion_price',
        'reference_number',
        'daily_price_history_id',
    ];

    protected $casts = [
        'promotion_price' => 'decimal:2',
    ];

    public function promotionItem(): BelongsTo
    {
        return $this->belongsTo(PromotionItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dailyPriceHistory(): BelongsTo
    {
        return $this->belongsTo(DailyPriceHistory::class);
    }
}
