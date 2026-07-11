<?php

namespace App\Modules\Core\Validation\Models;

use Illuminate\Database\Eloquent\Model;

class ValidationRuleSet extends Model
{
    protected $fillable = ['name', 'description', 'product_type_id', 'is_push_decision'];

    protected $casts = [
        'is_push_decision' => 'boolean',
    ];

    public function rules()
    {
        return $this->hasMany(ValidationRule::class, 'rule_set_id');
    }

    public function productType()
    {
        return $this->belongsTo(\App\Models\ProductType::class, 'product_type_id');
    }
}
