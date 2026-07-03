<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyPriceHistory extends Model
{
    protected $fillable = ['gold_price', 'tax_rate'];
}
