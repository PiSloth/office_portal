<?php

namespace App\Filament\Resources\ProductCheckResource\Pages;

use App\Filament\Resources\ProductCheckResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductCheck extends EditRecord
{
    protected static string $resource = ProductCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
