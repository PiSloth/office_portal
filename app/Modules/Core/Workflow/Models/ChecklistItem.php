<?php

namespace App\Modules\Core\Workflow\Models;

use Illuminate\Database\Eloquent\Model;

class ChecklistItem extends Model
{
    protected $fillable = ['checklist_id', 'label', 'is_required', 'is_active', 'sort_order'];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function checklist()
    {
        return $this->belongsTo(Checklist::class);
    }
}
