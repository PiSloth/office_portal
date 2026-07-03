<?php

namespace App\Filament\Resources\CalculationMethodResource\Pages;

use App\Filament\Resources\CalculationMethodResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCalculationMethods extends ListRecords
{
    protected static string $resource = CalculationMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
