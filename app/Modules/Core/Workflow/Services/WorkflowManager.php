<?php

namespace App\Modules\Core\Workflow\Services;

use App\Modules\Core\Workflow\Models\WorkflowState;
use App\Modules\Core\Workflow\Models\WorkflowTransition;
use Illuminate\Database\Eloquent\Model;

class WorkflowManager
{
    /**
     * Determine if a transition is allowed for the given model.
     */
    public function canTransition(Model $model, WorkflowTransition $transition, $user = null): bool
    {
        if ($model->workflow_state_id !== $transition->from_state_id) {
            return false;
        }

        if ($transition->required_permission && $user) {
            if (!$user->hasPermissionTo($transition->required_permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Transition the model to a new state.
     */
    public function transition(Model $model, WorkflowTransition $transition, $user = null): bool
    {
        if (!$this->canTransition($model, $transition, $user)) {
            return false;
        }

        // Run validation rules if they are set for this transition
        if ($transition->validation_rule_set_id) {
            $ruleSet = \App\Modules\Core\Validation\Models\ValidationRuleSet::find($transition->validation_rule_set_id);
            if ($ruleSet) {
                $validationManager = new \App\Modules\Core\Validation\Services\ValidationManager();
                $inputs = $model->toArray();
                if (!$validationManager->validate($model, $ruleSet, $inputs, $user?->getKey())) {
                    return false;
                }
            }
        }

        $model->workflow_state_id = $transition->to_state_id;
        
        if (method_exists($model, 'save')) {
            return $model->save();
        }

        return false;
    }
}
