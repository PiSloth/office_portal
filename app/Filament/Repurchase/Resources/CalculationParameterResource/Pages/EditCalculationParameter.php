<?php

namespace App\Filament\Repurchase\Resources\CalculationParameterResource\Pages;

use App\Filament\Repurchase\Resources\CalculationParameterResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCalculationParameter extends EditRecord
{
    protected static string $resource = CalculationParameterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
