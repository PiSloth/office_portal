<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnouncementGoldPrice extends Model
{
    protected $fillable = [
        'gold_price',
        'announcement_datetime',
        'user_id',
    ];

    protected $casts = [
        'announcement_datetime' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
