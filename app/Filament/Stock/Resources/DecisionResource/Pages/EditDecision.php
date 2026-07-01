<?php

namespace App\Filament\Stock\Resources\DecisionResource\Pages;

use App\Filament\Stock\Resources\DecisionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDecision extends EditRecord
{
    protected static string $resource = DecisionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
