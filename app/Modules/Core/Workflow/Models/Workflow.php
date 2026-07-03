<?php

namespace App\Modules\Core\Workflow\Models;

use Illuminate\Database\Eloquent\Model;

class Workflow extends Model
{
    protected $fillable = ['name', 'description', 'is_active', 'product_type_id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function states()
    {
        return $this->hasMany(WorkflowState::class);
    }

    public function transitions()
    {
        return $this->hasMany(WorkflowTransition::class);
    }

    public function productType()
    {
        return $this->belongsTo(\App\Models\ProductType::class, 'product_type_id');
    }
}
