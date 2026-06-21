<?php

namespace App\Filament\Resources\DecisionTypeResource\Pages;

use App\Filament\Resources\DecisionTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDecisionTypes extends ListRecords
{
    protected static string $resource = DecisionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
