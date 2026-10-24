<?php

namespace App\Filament\Stock\Resources\ProductCategoryMultiplierResource\Pages;

use App\Filament\Stock\Resources\ProductCategoryMultiplierResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductCategoryMultiplier extends EditRecord
{
    protected static string $resource = ProductCategoryMultiplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
