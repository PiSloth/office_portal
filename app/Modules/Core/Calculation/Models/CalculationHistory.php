<?php

namespace App\Modules\Core\Calculation\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CalculationHistory extends Model
{
    protected $fillable = [
        'parameter_snapshot_json', 
        'input_snapshot_json', 
        'total_amount', 
        'user_id'
    ];

    protected $casts = [
        'parameter_snapshot_json' => 'array',
        'input_snapshot_json' => 'array',
    ];

    public function calculatable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
