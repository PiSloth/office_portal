<?php

namespace App\Modules\Core\Workflow\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowState extends Model
{
    protected $fillable = ['workflow_id', 'name', 'color', 'is_start', 'is_end'];

    protected $casts = [
        'is_start' => 'boolean',
        'is_end' => 'boolean',
    ];

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }
}
