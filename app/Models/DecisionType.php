<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'code', 'description', 'is_active'])]
class DecisionType extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function decisions()
    {
        return $this->hasMany(Decision::class);
    }

    public function rules()
    {
        return $this->hasMany(DecisionRule::class);
    }
}
