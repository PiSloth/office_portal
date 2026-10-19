<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionSoldCancelLog extends Model
{
    protected $fillable = [
        'promotion_item_id',
        'user_id',
        'reference_number',
        'promotion_price',
        'reason',
    ];

    public function promotionItem(): BelongsTo
    {
        return $this->belongsTo(PromotionItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
