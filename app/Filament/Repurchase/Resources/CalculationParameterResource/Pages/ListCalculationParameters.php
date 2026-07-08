<?php

namespace App\Filament\Repurchase\Resources\CalculationParameterResource\Pages;

use App\Filament\Repurchase\Resources\CalculationParameterResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCalculationParameters extends ListRecords
{
    protected static string $resource = CalculationParameterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
