<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'description', 'started_by', 'started_at', 'completed_at', 'status'])]
class CheckSession extends Model
{
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function startedBy()
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function productChecks()
    {
        return $this->hasMany(ProductCheck::class);
    }
}
