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
    protected static string $permissionPrefix = 'gold-price';
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-currency-dollar';
    protected static \UnitEnum|string|null $navigationGroup = 'Purchase';
    protected static ?string $navigationLabel = 'Daily Gold Price';
    protected static ?string $title = 'Update Daily Gold Price & Tax';
    
    protected string $view = 'filament.pages.daily-price-setting';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('gold-price.view') ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('record_announcement')
                ->label('Record Official Announcement')
                ->icon('heroicon-o-bell')
                ->color('info')
                ->visible(fn (): bool => auth()->user()?->can('gold-price.create') ?? false)
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
                    abort_unless(auth()->user()?->can('gold-price.create'), 403);

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
                ->visible(fn (): bool => (auth()->user()?->can('gold-price.create') || auth()->user()?->can('gold-price.update')) ?? false)
                ->form([
                    TextInput::make('new_gold_price')
                        ->label('လက်ရှိ အရောင်း ပေါက်စျေး')
                        ->helperText(new \Illuminate\Support\HtmlString('<span style="color: #dc2626; font-size: 0.875rem;">Price Group မှ အရောင်း ပေါက်ဈေးကို ကူးယူ ထည့်သွင်းပါ။</span>'))
                        ->numeric()
                        ->required(),
                    TextInput::make('show_raw_goldprice')
                        ->label('အကျစ်ထည်ဈေး')
                        ->helperText(new \Illuminate\Support\HtmlString('<span style="color: #dc2626; font-size: 0.875rem;">Price Group မှ အကျစ်ထည်ဈေး ထည့်သွင်းပါ။</span>'))
                        ->numeric()
                        ->required(),
                    TextInput::make('tax')
                        ->label('Oth Charges (Tax / ခွာဈေး)')
                        ->helperText(new \Illuminate\Support\HtmlString('<span style="color: #dc2626; font-size: 0.875rem;">Price Group မှ အရောင်းဈေးနှင့် ပြန်ဝယ်ဈေးကို ခြားနား၍ ကွာဟသော ပမာဏကို ထည့်ပေးရန်။</span>'))
                        ->numeric()
                        ->required(),
                ])
                ->mountUsing(function ($form) {
                    $taxParam = CalculationParameter::where('key', 'tax_rate')->first();
                    $rawGoldParam = CalculationParameter::where('key', 'show_raw_goldprice')->first()
                        ?? CalculationParameter::where('key', 'raw_gold_price')->first();
                    $form->fill([
                        'tax' => $taxParam ? $taxParam->value : 0,
                        'show_raw_goldprice' => $rawGoldParam ? $rawGoldParam->value : 0,
                    ]);
                })
                ->action(function (array $data, DailyPriceSetting $livewire) {
                    abort_unless(auth()->user()?->can('gold-price.create') || auth()->user()?->can('gold-price.update'), 403);

                    $newGoldPrice = (float) $data['new_gold_price'];
                    $convertedGoldPrice = (16.606 / 16.3293) * $newGoldPrice;
                    $showRawGoldPrice = (float) ($data['show_raw_goldprice'] ?? 0);
                    $convertedRawGoldPrice = (16.606 / 16.3293) * $showRawGoldPrice;
                    $tax = (float) $data['tax'];

                    // Update generic engine parameters
                    CalculationParameter::updateOrCreate(
                        ['key' => 'base_gold_price'],
                        ['value' => $convertedGoldPrice, 'type' => 'numeric', 'method_id' => 1]
                    );

                    CalculationParameter::updateOrCreate(
                        ['key' => 'show_raw_goldprice'],
                        ['value' => $convertedRawGoldPrice, 'type' => 'numeric', 'method_id' => 1]
                    );

                    CalculationParameter::updateOrCreate(
                        ['key' => 'raw_gold_price'],
                        ['value' => $convertedRawGoldPrice, 'type' => 'numeric', 'method_id' => 1]
                    );

                    CalculationParameter::updateOrCreate(
                        ['key' => 'tax_rate'],
                        ['value' => $tax, 'type' => 'numeric', 'method_id' => 1]
                    );

                    // Keep historical record
                    \App\Models\DailyPriceHistory::create([
                        'gold_price' => $convertedGoldPrice,
                        'raw_gold_price' => $convertedRawGoldPrice,
                        'tax_rate' => $tax,
                        'user_id' => auth()->id(),
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
        $rawGoldPriceParam = CalculationParameter::where('key', 'show_raw_goldprice')->first()
            ?? CalculationParameter::where('key', 'raw_gold_price')->first();
        $taxParam = CalculationParameter::where('key', 'tax_rate')->first();

        $this->form->fill([
            'gold_price' => $goldPriceParam ? $goldPriceParam->value : 0,
            'show_raw_goldprice' => $rawGoldPriceParam ? $rawGoldPriceParam->value : 0,
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
                        TextInput::make('show_raw_goldprice')
                            ->label('အကျစ်ထည်ဈေး')
                            ->numeric()
                            ->required()
                            ->disabled()
                            ->dehydrated(),
                        TextInput::make('tax')
                            ->label('Oth Charges (Tax / ခွာဈေး)')
                            ->numeric()
                            ->required()
                            ->disabled(fn (): bool => ! (auth()->user()?->can('gold-price.update') ?? false)),
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
                ->color('danger')
                ->visible(fn (): bool => auth()->user()?->can('gold-price.update') ?? false),
        ];
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->can('gold-price.update'), 403);

        $state = $this->data; // Fixed to read from Livewire property since statePath is on the schema root

        // Update generic engine parameters
        CalculationParameter::updateOrCreate(
            ['key' => 'base_gold_price'],
            ['value' => $state['gold_price'], 'type' => 'numeric', 'method_id' => 1]
        );

        CalculationParameter::updateOrCreate(
            ['key' => 'show_raw_goldprice'],
            ['value' => $state['show_raw_goldprice'] ?? 0, 'type' => 'numeric', 'method_id' => 1]
        );

        CalculationParameter::updateOrCreate(
            ['key' => 'raw_gold_price'],
            ['value' => $state['show_raw_goldprice'] ?? 0, 'type' => 'numeric', 'method_id' => 1]
        );

        CalculationParameter::updateOrCreate(
            ['key' => 'tax_rate'],
            ['value' => $state['tax'], 'type' => 'numeric', 'method_id' => 1]
        );

        // Keep historical record
        \App\Models\DailyPriceHistory::create([
            'gold_price' => $state['gold_price'],
            'raw_gold_price' => $state['show_raw_goldprice'] ?? 0,
            'tax_rate' => $state['tax'],
            'user_id' => auth()->id(),
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
                TextColumn::make('user.name')
                    ->label('Changed By')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('user.branch.name')
                    ->label('Branch')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('gold_price')
                    ->label('Gold Price (MMK)')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('raw_gold_price')
                    ->label('အကျစ်ထည်ဈေး (MMK)')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('tax_rate')
                    ->label('Oth Charges / Tax')
                    ->numeric()
                    ->sortable(),
            ])
            ->actions([
                \Filament\Actions\DeleteAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('gold-price.delete') ?? false),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->can('gold-price.delete') ?? false),
                ]),
            ])
            ->paginated([5, 10, 25, 50])
            ->defaultPaginationPageOption(5);
    }
}
