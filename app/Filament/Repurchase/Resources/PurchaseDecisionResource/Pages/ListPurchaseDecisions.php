<?php

namespace App\Filament\Repurchase\Resources\PurchaseDecisionResource\Pages;

use App\Filament\Repurchase\Resources\PurchaseDecisionResource;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseDecisions extends ListRecords
{
    protected static string $resource = PurchaseDecisionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
