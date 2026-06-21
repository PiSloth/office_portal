<?php

namespace App\Filament\Resources\ScanConfigResource\Pages;

use App\Filament\Resources\ScanConfigResource;
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
