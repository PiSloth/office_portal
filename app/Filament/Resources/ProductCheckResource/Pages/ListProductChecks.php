<?php

namespace App\Filament\Resources\ProductCheckResource\Pages;

use App\Filament\Resources\ProductCheckResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductChecks extends ListRecords
{
    protected static string $resource = ProductCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
