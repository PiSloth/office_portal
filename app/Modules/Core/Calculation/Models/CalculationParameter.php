<?php

namespace App\Modules\Core\Calculation\Models;

use Illuminate\Database\Eloquent\Model;

class CalculationParameter extends Model
{
    protected $fillable = ['method_id', 'key', 'value', 'type'];

    public function method()
    {
        return $this->belongsTo(CalculationMethod::class, 'method_id');
    }
}
