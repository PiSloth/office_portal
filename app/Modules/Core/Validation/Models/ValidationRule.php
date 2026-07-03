<?php

namespace App\Modules\Core\Validation\Models;

use Illuminate\Database\Eloquent\Model;

class ValidationRule extends Model
{
    protected $fillable = [
        'rule_set_id', 
        'label',
        'field_name', 
        'operator', 
        'expected_source', 
        'tolerance', 
        'is_required', 
        'is_editable'
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_editable' => 'boolean',
    ];

    public function ruleSet()
    {
        return $this->belongsTo(ValidationRuleSet::class, 'rule_set_id');
    }
}
