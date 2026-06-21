<?php

namespace App\Listeners;

use App\Events\ProductChecked;
use App\Services\DecisionWorkflowService;

class RunDecisionEngine
{
    protected DecisionWorkflowService $workflowService;

    /**
     * Create the event listener.
     */
    public function __construct(DecisionWorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    /**
     * Handle the event.
     */
    public function handle(ProductChecked $event): void
    {
        $this->workflowService->evaluateRulesAndCreateDecisions($event->productCheck);
    }
}
