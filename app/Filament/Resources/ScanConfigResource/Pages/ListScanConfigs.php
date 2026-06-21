<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScanConfigResource\Pages;
use App\Models\ScanConfig;

namespace App\Filament\Resources\ScanConfigResource\Pages;

use App\Filament\Resources\ScanConfigResource;
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
