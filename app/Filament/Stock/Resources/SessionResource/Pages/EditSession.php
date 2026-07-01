<?php

namespace App\Filament\Stock\Resources\SessionResource\Pages;

use App\Filament\Stock\Resources\SessionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSession extends EditRecord
{
    protected static string $resource = SessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
