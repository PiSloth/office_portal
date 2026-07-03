<?php

namespace App\Modules\Core\Calculation\Models;

use Illuminate\Database\Eloquent\Model;

class CalculationMethod extends Model
{
    protected $fillable = ['name', 'php_class_name'];

    public function parameters()
    {
        return $this->hasMany(CalculationParameter::class, 'method_id');
    }
}
