<?php

namespace App\Filament\Stock\Resources\ScanConfigResource\Pages;

use App\Filament\Stock\Resources\ScanConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditScanConfig extends EditRecord
{
    protected static string $resource = ScanConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
