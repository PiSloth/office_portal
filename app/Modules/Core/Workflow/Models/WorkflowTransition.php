<?php

namespace App\Modules\Core\Workflow\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowTransition extends Model
{
    protected $fillable = ['workflow_id', 'from_state_id', 'to_state_id', 'action_name', 'required_permission', 'validation_rule_set_id', 'checklist_id'];

    public function checklist()
    {
        return $this->belongsTo(\App\Modules\Core\Workflow\Models\Checklist::class, 'checklist_id');
    }

    public function validationRuleSet()
    {
        return $this->belongsTo(\App\Modules\Core\Validation\Models\ValidationRuleSet::class, 'validation_rule_set_id');
    }

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }

    public function fromState()
    {
        return $this->belongsTo(WorkflowState::class, 'from_state_id');
    }

    public function toState()
    {
        return $this->belongsTo(WorkflowState::class, 'to_state_id');
    }
}
