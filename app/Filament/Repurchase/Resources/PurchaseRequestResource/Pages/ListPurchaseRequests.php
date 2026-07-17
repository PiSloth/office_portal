<?php

namespace App\Filament\Repurchase\Resources\PurchaseRequestResource\Pages;

use App\Filament\Repurchase\Resources\PurchaseRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseRequests extends ListRecords
{
    protected static string $resource = PurchaseRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print_report')
                ->label('Print Items Report')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->modalHeading('Print Purchased Items Report')
                ->modalWidth('lg')
                ->form([
                    \Filament\Forms\Components\Select::make('branch_id')
                        ->label('Location (Branch)')
                        ->options(\App\Models\Branch::pluck('name', 'id'))
                        ->placeholder('All Locations'),
                    \Filament\Forms\Components\DatePicker::make('date')
                        ->label('Report Date')
                        ->required()
                        ->default(now()),
                    \Filament\Forms\Components\Select::make('state_filter')
                        ->label('Workflow State')
                        ->options([
                            'all' => 'All States',
                            'before_paid' => 'Before Paid',
                            'paid_and_after' => 'Paid & After',
                        ])
                        ->default('all')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $queryParams = http_build_query([
                        'branch_id' => $data['branch_id'] ?? '',
                        'date' => $data['date'] ?? '',
                        'state_filter' => $data['state_filter'] ?? 'all',
                    ]);
                    
                    $this->js("window.open('" . route('purchase-requests.report.print') . '?' . $queryParams . "', '_blank')");
                }),
            Actions\CreateAction::make(),
        ];
    }
}
