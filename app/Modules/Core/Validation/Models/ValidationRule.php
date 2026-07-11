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
        'is_editable',
        'is_based_grade',
        'grades_json',
        'is_skip_zero'
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_editable' => 'boolean',
        'is_based_grade' => 'boolean',
        'grades_json' => 'array',
        'is_skip_zero' => 'boolean',
    ];

    public function ruleSet()
    {
        return $this->belongsTo(ValidationRuleSet::class, 'rule_set_id');
    }
}
