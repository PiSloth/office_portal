<?php

namespace App\Filament\Repurchase\Resources\PurchaseRequestResource\Pages;

use App\Filament\Repurchase\Resources\PurchaseRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseRequest extends CreateRecord
{
    protected static string $resource = PurchaseRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['status_updated_by_id'] = auth()->id();
        
        $productTypeId = $data['product_type_id'] ?? null;
        $workflow = null;
        if ($productTypeId) {
            $workflow = \App\Modules\Core\Workflow\Models\Workflow::where('product_type_id', $productTypeId)
                ->where('is_active', true)
                ->first();
        }
        
        $startState = null;
        if ($workflow) {
            $startState = $workflow->states()->where('is_start', true)->first();
        }
        
        if (!$startState) {
            $startState = \App\Modules\Core\Workflow\Models\WorkflowState::where('is_start', true)->first();
        }
        
        if ($startState) {
            $data['workflow_state_id'] = $startState->id;
        }
        
        return $data;
    }

    protected function beforeCreate(): void
    {
        if (!auth()->user()?->branch_id) {
            \Filament\Notifications\Notification::make()
                ->title('Unauthorized')
                ->body("You must belong to a branch to create purchase requests.")
                ->danger()
                ->send();
                
            $this->halt();
        }

        if (empty($this->data['customer_name'])) {
            \Filament\Notifications\Notification::make()
                ->title('Customer Info Required')
                ->body('Customer နာမည် မဖြစ်မနေထည့်မှသာ ရှေ့ဆက်ခွင့်ရှိသည်။')
                ->danger()
                ->send();
                
            $this->halt();
        }

        if (empty($this->data['items'])) {
            \Filament\Notifications\Notification::make()
                ->title('Validation Error')
                ->body("We can't create when no purchase items are prepared.")
                ->danger()
                ->send();
                
            $this->halt();
        }
    }
}
