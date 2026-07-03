<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Modules\Core\Calculation\Models\CalculationParameter;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class DailyPriceSetting extends Page implements HasTable
{
    use InteractsWithTable;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-currency-dollar';
    protected static \UnitEnum|string|null $navigationGroup = 'Purchase';
    protected static ?string $navigationLabel = 'Daily Gold Price';
    protected static ?string $title = 'Update Daily Gold Price & Tax';
    
    protected string $view = 'filament.pages.daily-price-setting';

    public ?array $data = [];

    public function mount(): void
    {
        $goldPriceParam = CalculationParameter::where('key', 'base_gold_price')->first();
        $taxParam = CalculationParameter::where('key', 'tax_rate')->first();

        $this->form->fill([
            'gold_price' => $goldPriceParam ? $goldPriceParam->value : 0,
            'tax' => $taxParam ? $taxParam->value : 0,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Update Today\'s Prices')
                    ->description('This updates the global calculation parameters for all purchases.')
                    ->schema([
                        TextInput::make('gold_price')
                            ->label('Gold Price')
                            ->numeric()
                            ->required(),
                        TextInput::make('tax')
                            ->label('Oth Charges (Tax / ခွာဈေး)')
                            ->numeric()
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Update Prices')
                ->submit('save')
                ->color('danger'),
        ];
    }

    public function save(): void
    {
        $state = $this->data; // Fixed to read from Livewire property since statePath is on the schema root

        // Update generic engine parameters
        CalculationParameter::updateOrCreate(
            ['key' => 'base_gold_price'],
            ['value' => $state['gold_price'], 'type' => 'numeric', 'method_id' => 1]
        );

        CalculationParameter::updateOrCreate(
            ['key' => 'tax_rate'],
            ['value' => $state['tax'], 'type' => 'numeric', 'method_id' => 1]
        );

        // Keep historical record
        \App\Models\DailyPriceHistory::create([
            'gold_price' => $state['gold_price'],
            'tax_rate' => $state['tax'],
        ]);

        Notification::make()
            ->title('Prices Updated Successfully')
            ->success()
            ->send();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(\App\Models\DailyPriceHistory::query()->latest())
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date & Time')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
                TextColumn::make('gold_price')
                    ->label('Gold Price (MMK)')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('tax_rate')
                    ->label('Oth Charges / Tax')
                    ->numeric()
                    ->sortable(),
            ])
            ->paginated([5, 10, 25, 50])
            ->defaultPaginationPageOption(5);
    }
}
