<?php

namespace App\Filament\Stock\Resources\DecisionResource\Pages;

use App\Filament\Stock\Resources\DecisionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDecisions extends ListRecords
{
    protected static string $resource = DecisionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
