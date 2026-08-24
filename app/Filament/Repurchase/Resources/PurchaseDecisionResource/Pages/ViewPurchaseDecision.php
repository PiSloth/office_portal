<?php

namespace App\Filament\Repurchase\Resources\PurchaseDecisionResource\Pages;

use App\Filament\Repurchase\Resources\PurchaseDecisionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseDecision extends ViewRecord
{
    protected static string $resource = PurchaseDecisionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
