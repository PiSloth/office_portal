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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('record_announcement')
                ->label('Record Official Announcement')
                ->icon('heroicon-o-bell')
                ->color('info')
                ->form([
                    TextInput::make('gold_price')
                        ->label('Official Gold Price')
                        ->numeric()
                        ->required(),
                    \Filament\Forms\Components\DateTimePicker::make('announcement_datetime')
                        ->label('Announcement Date & Time')
                        ->default(now())
                        ->required(),
                ])
                ->action(function (array $data) {
                    \App\Models\AnnouncementGoldPrice::create([
                        'gold_price' => $data['gold_price'],
                        'announcement_datetime' => $data['announcement_datetime'],
                        'user_id' => auth()->id(),
                    ]);

                    Notification::make()
                        ->title('Official Announcement Recorded Successfully')
                        ->success()
                        ->send();
                }),
            Action::make('convert_price')
                ->label('Convert & Update Price')
                ->modalDescription('Viber Group တွင်ကြေငြာသော 16.3293 ၏ ဈေးကို ကူးယူ၍ ဤနေရာတွင် ရေးပါ။')
                ->icon('heroicon-o-calculator')
                ->color('warning')
                ->form([
                    TextInput::make('new_gold_price')
                        ->label('New Gold Price')
                        ->numeric()
                        ->required(),
                    TextInput::make('tax')
                        ->label('Oth Charges (Tax / ခွာဈေး)')
                        ->numeric()
                        ->required(),
                ])
                ->mountUsing(function ($form) {
                    $taxParam = CalculationParameter::where('key', 'tax_rate')->first();
                    $form->fill([
                        'tax' => $taxParam ? $taxParam->value : 0,
                    ]);
                })
                ->action(function (array $data, DailyPriceSetting $livewire) {
                    $newGoldPrice = (float) $data['new_gold_price'];
                    $convertedGoldPrice = (16.606 / 16.3293) * $newGoldPrice;
                    $tax = (float) $data['tax'];

                    // Update generic engine parameters
                    CalculationParameter::updateOrCreate(
                        ['key' => 'base_gold_price'],
                        ['value' => $convertedGoldPrice, 'type' => 'numeric', 'method_id' => 1]
                    );

                    CalculationParameter::updateOrCreate(
                        ['key' => 'tax_rate'],
                        ['value' => $tax, 'type' => 'numeric', 'method_id' => 1]
                    );

                    // Keep historical record
                    \App\Models\DailyPriceHistory::create([
                        'gold_price' => $convertedGoldPrice,
                        'tax_rate' => $tax,
                    ]);

                    Notification::make()
                        ->title('Prices Converted and Updated Successfully')
                        ->success()
                        ->send();

                    $livewire->redirect(static::getUrl());
                }),
        ];
    }

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
                            ->required()
                            ->disabled()
                            ->dehydrated(),
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
