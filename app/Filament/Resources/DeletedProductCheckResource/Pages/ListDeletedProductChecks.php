<?php

namespace App\Filament\Resources\DeletedProductCheckResource\Pages;

use App\Filament\Resources\DeletedProductCheckResource;
use Filament\Resources\Pages\ListRecords;

class ListDeletedProductChecks extends ListRecords
{
    protected static string $resource = DeletedProductCheckResource::class;
}
