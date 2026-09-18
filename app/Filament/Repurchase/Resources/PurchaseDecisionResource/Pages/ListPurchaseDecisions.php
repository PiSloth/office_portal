<?php

namespace App\Filament\Repurchase\Resources\PurchaseDecisionResource\Pages;

use App\Filament\Repurchase\Resources\PurchaseDecisionResource;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseDecisions extends ListRecords
{
    protected static string $resource = PurchaseDecisionResource::class;

    public ?string $tableSort = 'created_at:desc';

    public ?string $tableGrouping = 'purchase_request_id:desc';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
