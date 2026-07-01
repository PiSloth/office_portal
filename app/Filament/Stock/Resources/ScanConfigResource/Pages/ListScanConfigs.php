<?php

namespace App\Filament\Stock\Resources;

use App\Filament\Stock\Resources\ScanConfigResource\Pages;
use App\Models\ScanConfig;

namespace App\Filament\Stock\Resources\ScanConfigResource\Pages;

use App\Filament\Stock\Resources\ScanConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListScanConfigs extends ListRecords
{
    protected static string $resource = ScanConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
