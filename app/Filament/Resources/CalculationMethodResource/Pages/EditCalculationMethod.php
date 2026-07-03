<?php

namespace App\Filament\Resources\CalculationMethodResource\Pages;

use App\Filament\Resources\CalculationMethodResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCalculationMethod extends EditRecord
{
    protected static string $resource = CalculationMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
