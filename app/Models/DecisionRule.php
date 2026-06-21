<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'criteria_field', 'criteria_condition', 'decision_type_id', 'is_active'])]
class DecisionRule extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function decisionType()
    {
        return $this->belongsTo(DecisionType::class);
    }
}
