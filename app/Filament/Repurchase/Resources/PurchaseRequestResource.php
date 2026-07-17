<?php

namespace App\Filament\Repurchase\Resources;

use App\Filament\Resources\Concerns\HasPermissionGates;
use App\Filament\Repurchase\Resources\PurchaseRequestResource\Pages;
use App\Modules\Purchase\Models\PurchaseRequest;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PurchaseRequestResource extends Resource
{
    use HasPermissionGates;
    protected static string $permissionPrefix = 'purchase-requests';
    protected static ?string $model = PurchaseRequest::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-shopping-bag';
    protected static \UnitEnum|string|null $navigationGroup = 'Purchase';

    public static function canCreate(): bool
    {
        return auth()->user()?->branch_id !== null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Customer Details')
                    ->headerActions([
                        \Filament\Actions\Action::make('edit_customer')
                            ->label('Edit Customer Info')
                            ->icon('heroicon-m-pencil-square')
                            ->modalHeading('Update Customer Information')
                            ->modalWidth('md')
                            ->form([
                                Forms\Components\TextInput::make('customer_name')
                                    ->label('Customer Name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('customer_phone')
                                    ->label('Phone Number')
                                    ->tel()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('customer_address')
                                    ->label('Address')
                                    ->maxLength(500)
                                    ->rows(3),
                                Forms\Components\TextInput::make('customer_nrc')
                                    ->label('NRC (Optional)')
                                    ->maxLength(255)
                                    ->live(),
                                Forms\Components\FileUpload::make('customer_nrc_photo')
                                    ->label('NRC Photo (Optional)')
                                    ->image()
                                    ->multiple()
                                    ->disk('public')
                                    ->visibility('public')
                                    ->directory('attachments/nrc_photos')
                                    ->visible(fn(\Filament\Schemas\Components\Utilities\Get $get) => filled($get('customer_nrc'))),
                            ])
                            ->mountUsing(function (\Filament\Schemas\Schema $form, \Filament\Schemas\Components\Utilities\Get $get) {
                                $form->fill([
                                    'customer_name' => $get('customer_name'),
                                    'customer_phone' => $get('customer_phone'),
                                    'customer_address' => $get('customer_address'),
                                    'customer_nrc' => $get('customer_nrc'),
                                    'customer_nrc_photo' => $get('customer_nrc_photo'),
                                ]);
                            })
                            ->action(function (array $data, \Filament\Schemas\Components\Utilities\Set $set) {
                                $set('customer_name', $data['customer_name']);
                                $set('customer_phone', $data['customer_phone']);
                                $set('customer_address', $data['customer_address']);
                                $set('customer_nrc', $data['customer_nrc'] ?? null);
                                $set('customer_nrc_photo', $data['customer_nrc_photo'] ?? null);
                            })
                    ])
                    ->schema([
                        // Hidden fields to store state
                        Forms\Components\Hidden::make('customer_name')
                            ->required()
                            ->live(),
                        Forms\Components\Hidden::make('customer_phone')
                            ->live(),
                        Forms\Components\Hidden::make('customer_address')
                            ->live(),
                        Forms\Components\Hidden::make('customer_nrc')
                            ->live(),
                        Forms\Components\Hidden::make('customer_nrc_photo')
                            ->live(),
                        Forms\Components\Hidden::make('branch_id')
                            ->default(fn() => auth()->user()?->branch_id),
                        Forms\Components\Hidden::make('product_type_id')
                            ->default(fn() => \App\Models\ProductType::where('code', 'JEWELRY')->orWhere('name', 'jewelry')->first()?->id),

                        \Filament\Schemas\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Placeholder::make('purchase_number')
                                    ->label('Purchase Number')
                                    ->content(fn($record) => $record?->purchase_number ?? 'PR-Draft')
                                    ->visible(fn($record) => $record !== null && $record->exists),
                                Forms\Components\Placeholder::make('branch_name')
                                    ->label('Branch')
                                    ->content(fn($record) => $record?->branch?->name ?? auth()->user()?->branch?->name ?? 'No Branch'),
                                Forms\Components\Placeholder::make('product_type_name')
                                    ->label('Product Type')
                                    ->content(fn($record) => $record?->productType?->name ?? '-')
                                    ->visible(fn($record) => $record !== null && $record->exists),
                                Forms\Components\Placeholder::make('workflow_state_name')
                                    ->label('Status')
                                    ->content(function ($record) {
                                        $state = $record?->workflowState;
                                        if (!$state) {
                                            return new \Illuminate\Support\HtmlString('<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200">Draft</span>');
                                        }
                                        $name = $state->name;
                                        $color = $state->color ?: 'gray';

                                        $classes = match ($color) {
                                            'blue' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-200',
                                            'success' => 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-200',
                                            'danger' => 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200',
                                            'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-200',
                                            default => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
                                        };

                                        return new \Illuminate\Support\HtmlString('<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider ' . $classes . '">' . e($name) . '</span>');
                                    }),
                                Forms\Components\Placeholder::make('total_amount_disp')
                                    ->label('Total Cost')
                                    ->content(fn($record) => number_format($record?->total_amount ?? 0) . ' MMK'),
                                Forms\Components\Placeholder::make('view_decision')
                                    ->label('Related Decision')
                                    ->visible(fn($record) => $record !== null && $record->purchaseDecision !== null)
                                    ->content('Click view details button to read action details.')
                                    ->hintAction(
                                        \Filament\Actions\Action::make('view_decision_modal')
                                            ->label('View Details')
                                            ->icon('heroicon-o-shield-exclamation')
                                            ->color('warning')
                                            ->modalSubmitAction(false)
                                            ->modalCancelActionLabel('Close')
                                            ->modalHeading('Related Purchase Decision')
                                            ->form(fn($record) => [
                                                Forms\Components\TextInput::make('status')
                                                    ->default($record->purchaseDecision?->status)
                                                    ->disabled()
                                                    ->label('Decision Status'),
                                                Forms\Components\Textarea::make('remark')
                                                    ->default($record->purchaseDecision?->remark)
                                                    ->disabled()
                                                    ->label('Remarks / Action Taken'),
                                                Forms\Components\Placeholder::make('attachments_info')
                                                    ->label('Proof of Resolution (Images)')
                                                    ->content(function () use ($record) {
                                                        $attachments = $record->purchaseDecision?->attachments;
                                                        if (!$attachments || $attachments->isEmpty()) {
                                                            return 'No attachments uploaded.';
                                                        }
                                                        $html = '<div class="space-y-2">';
                                                        foreach ($attachments as $att) {
                                                            $url = asset('storage/' . $att->file_path);
                                                            $html .= '<div class="flex items-center space-x-2">';
                                                            $html .= '<a href="' . e($url) . '" target="_blank" class="text-teal-600 dark:text-teal-400 underline font-semibold">' . e($att->file_name) . '</a>';
                                                            $html .= '</div>';
                                                        }
                                                        $html .= '</div>';
                                                        return new \Illuminate\Support\HtmlString($html);
                                                    }),
                                            ])
                                    ),
                            ]),

                        Forms\Components\Placeholder::make('customer_info_card')
                            ->hiddenLabel()
                            ->content(function ($record, \Filament\Schemas\Components\Utilities\Get $get) {
                                $name = $get('customer_name') ?: $record?->customer_name ?: 'Not Provided';
                                $phone = $get('customer_phone') ?: $record?->customer_phone ?: 'Not Provided';
                                $address = $get('customer_address') ?: $record?->customer_address ?: 'Not Provided';
                                $nrc = $get('customer_nrc') ?: $record?->customer_nrc;
                                $nrcPhoto = $get('customer_nrc_photo') ?: $record?->customer_nrc_photo;

                                $html = '<div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-800 space-y-2 text-sm max-w-md shadow-sm">';
                                $html .= '<h3 class="text-xs font-bold text-primary-600 dark:text-primary-400 uppercase tracking-wider">Customer Contact Info</h3>';
                                $html .= '<hr class="border-gray-200 dark:border-gray-800" />';
                                $html .= '<div><span class="text-gray-500 dark:text-gray-400 font-medium text-xs">Customer Name:</span> <strong class="text-gray-800 dark:text-gray-100 block text-base mt-0.5">' . e($name) . '</strong></div>';
                                $html .= '<div><span class="text-gray-500 dark:text-gray-400 font-medium text-xs">Phone Number:</span> <span class="text-gray-800 dark:text-gray-200 block font-semibold mt-0.5">' . e($phone) . '</span></div>';
                                $html .= '<div><span class="text-gray-500 dark:text-gray-400 font-medium text-xs">Address:</span> <span class="text-gray-700 dark:text-gray-300 block text-xs mt-0.5 whitespace-pre-line">' . e($address) . '</span></div>';
                                if ($nrc) {
                                    $html .= '<div><span class="text-gray-500 dark:text-gray-400 font-medium text-xs">NRC:</span> <span class="text-gray-800 dark:text-gray-200 block font-semibold mt-0.5">' . e($nrc) . '</span></div>';
                                    if ($nrcPhoto) {
                                        $photos = is_array($nrcPhoto) ? $nrcPhoto : (json_decode($nrcPhoto, true) ?: [$nrcPhoto]);
                                        $html .= '<div><span class="text-gray-500 dark:text-gray-400 font-medium text-xs">NRC Photo:</span> ';
                                        foreach ($photos as $index => $photo) {
                                            $url = asset('storage/' . $photo);
                                            $html .= '<a href="' . e($url) . '" target="_blank" class="text-teal-600 dark:text-teal-400 underline font-semibold mt-0.5 mr-2">Photo ' . ($index + 1) . '</a>';
                                        }
                                        $html .= '</div>';
                                    }
                                }
                                $html .= '</div>';
                                return new \Illuminate\Support\HtmlString($html);
                            }),
                    ]),

                \Filament\Schemas\Components\Section::make('Purchase Items (Calculator)')
                    ->columnSpan('full')
                    ->visible(fn(\Filament\Schemas\Components\Utilities\Get $get) => filled($get('product_type_id')))
                    ->extraAttributes(['class' => 'min-w-0 max-w-full'])
                    ->headerActions([
                        \Filament\Actions\Action::make('add_gb_product')
                            ->hidden(fn($record) => !self::isDraft($record) && self::isAllVerified($record))
                            ->label('GB Calculator')
                            ->icon('heroicon-m-calculator')
                            ->extraAttributes(['style' => 'background-color: #fffff0; color: #3d3d29; border: 1px solid #d1d1c4; font-weight: bold;'])
                            ->modalHeading('GB Product Calculator')
                            ->modalWidth('4xl')
                            ->form([
                                \Filament\Schemas\Components\Group::make([
                                    Forms\Components\TextInput::make('product_name')
                                        ->label('Product Name')
                                        ->required()
                                        ->extraInputAttributes(['onkeydown' => 'if (event.key === "Enter") { event.preventDefault(); }']),

                                    Forms\Components\Hidden::make('purchase_type')
                                        ->default('gb_product'),

                                    Forms\Components\Toggle::make('is_good')->offColor('danger')->onColor('success')
                                        ->label(
                                            fn($state) => $state
                                                ? new \Illuminate\Support\HtmlString('<span style="color: green; font-weight: bold;">ရ</span>')
                                                : new \Illuminate\Support\HtmlString('<span style="color: red; font-weight: bold;">မရ</span>')
                                        )
                                        ->live()
                                        ->default(false),

                                    \Filament\Schemas\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\Select::make('goldList')
                                                ->label('Grade of Gold')
                                                ->options(self::getGoldGradeOptions('gb_product'))
                                                ->required()
                                                ->live(),

                                            Forms\Components\Select::make('reChange')
                                                ->label('ပြန်ဝယ်အမျိုးအစား?')
                                                ->options([
                                                    '0' => 'ဆိုင်ထည် (No)',
                                                    '1' => 'အလဲအထပ်လုပ်မယ် (Yes)',
                                                    '2' => 'Percent ထည်ပြန်ဝယ်',
                                                ])
                                                ->default('0')
                                                ->live(),
                                        ]),

                                    Forms\Components\TextInput::make('original_voucher_price')
                                        ->numeric()
                                        ->label('Original Voucher Price')
                                        ->required(fn(\Filament\Schemas\Components\Utilities\Get $get) => (string)$get('reChange') === '2')
                                        ->visible(fn(\Filament\Schemas\Components\Utilities\Get $get) => (string)$get('reChange') === '2')
                                        ->live(onBlur: true)
                                        ->extraInputAttributes(['onkeydown' => 'if (event.key === "Enter") { event.preventDefault(); }']),

                                    Forms\Components\FileUpload::make('attachment_image')
                                        ->label('Attachment Image')
                                        ->image()
                                        ->disk('public')
                                        ->visibility('public')
                                        ->directory('attachments/purchase_items')
                                        ->required(fn(\Filament\Schemas\Components\Utilities\Get $get) => (string)$get('reChange') === '2')
                                        ->visible(fn(\Filament\Schemas\Components\Utilities\Get $get) => (string)$get('reChange') === '2'),

                                    \Filament\Schemas\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('goldWeightGram')
                                                ->numeric()
                                                ->label('Gram (g)')
                                                ->default(0)
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(fn(\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set) => self::convertGramToKyat($get, $set))
                                                ->extraInputAttributes(['onkeydown' => 'if (event.key === "Enter") { event.preventDefault(); }']),
                                            Forms\Components\TextInput::make('kyaukWeight')
                                                ->numeric()
                                                ->label('ကျောက်ချိန် (ရွေး)')
                                                ->default(0)
                                                ->live(onBlur: true)
                                                ->extraInputAttributes(['onkeydown' => 'if (event.key === "Enter") { event.preventDefault(); }']),
                                        ]),

                                    Forms\Components\Toggle::make('show_KPY')
                                        ->label('Show KPY (ကျပ်၊ ပဲ၊ ရွေး)')
                                        ->live()
                                        ->afterStateHydrated(function ($component, $state, $get) {
                                            $hasKpy = (float)($get('kyat') ?? 0) > 0 || (float)($get('pae') ?? 0) > 0 || (float)($get('yawe') ?? 0) > 0;
                                            $component->state((bool)($state || $hasKpy));
                                        }),

                                    \Filament\Schemas\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\TextInput::make('kyat')
                                                ->numeric()
                                                ->label('ကျပ်')
                                                ->default(0)
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(fn(\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set) => self::convertKyatToGram($get, $set))
                                                ->extraInputAttributes(['onkeydown' => 'if (event.key === "Enter") { event.preventDefault(); }']),
                                            Forms\Components\TextInput::make('pae')
                                                ->numeric()
                                                ->label('ပဲ')
                                                ->default(0)
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(fn(\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set) => self::convertKyatToGram($get, $set))
                                                ->extraInputAttributes(['onkeydown' => 'if (event.key === "Enter") { event.preventDefault(); }']),
                                            Forms\Components\TextInput::make('yawe')
                                                ->numeric()
                                                ->label('ရွေး')
                                                ->default(0)
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(fn(\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set) => self::convertKyatToGram($get, $set))
                                                ->extraInputAttributes(['onkeydown' => 'if (event.key === "Enter") { event.preventDefault(); }']),
                                        ])
                                        ->visible(fn(\Filament\Schemas\Components\Utilities\Get $get) => (bool)$get('show_KPY')),

                                    \Filament\Schemas\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\TextInput::make('percent')
                                                ->numeric()
                                                ->label('Percent Deduction')
                                                ->suffix('%')
                                                ->default(0)
                                                ->required(fn(\Filament\Schemas\Components\Utilities\Get $get) => (string)$get('reChange') === '2')
                                                ->live(onBlur: true)
                                                ->helperText(new \Illuminate\Support\HtmlString('<span class="percent-info-text">ရာခိုင်နှုန်းလျော့ထည့်ရန်</span>'))
                                                ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'ရာခိုင်နှုန်းလျော့ထည့်ရန်')
                                                ->extraInputAttributes([
                                                    'onkeydown' => 'if (event.key === "Enter") { event.preventDefault(); }',
                                                    'title' => 'ရာခိုင်နှုန်းလျော့ထည့်ရန်',
                                                ])
                                                ->extraAttributes(['class' => 'percent-field']),

                                            Forms\Components\TextInput::make('quantity')
                                                ->numeric()
                                                ->label('အရေအတွက်')
                                                ->suffix('qty')
                                                ->required()
                                                ->default(1)
                                                ->minValue(1)
                                                ->extraInputAttributes(['onkeydown' => 'if (event.key === "Enter") { event.preventDefault(); }']),
                                        ]),

                                    Forms\Components\Textarea::make('remark')
                                        ->label('Remark')
                                        ->nullable()
                                        ->rows(2)
                                        ->extraInputAttributes(['onkeydown' => 'if (event.key === "Enter") { event.preventDefault(); }']),
                                ])
                                    ->extraAttributes(['style' => 'background-color: #fffff0; border-radius: 8px; padding: 24px; border: 1px solid #d1d1c4;'])
                            ])
                            ->action(function (array $data, \Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $livewire) {
                                $method = \App\Modules\Core\Calculation\Models\CalculationMethod::where('name', 'Standard Jewelry Calculator')->first();
                                $parameters = $method ? $method->parameters->pluck('value', 'key')->toArray() : [];

                                $multiplier = self::resolveMultiplierValue($data['goldList'], 'gb_product');
                                $data['multiplier'] = $multiplier;
                                $data['purchase_type'] = 'gb_product';

                                $strategy = new \App\Modules\Core\Calculation\Strategies\JewelryCalculator();
                                $result = $strategy->calculate($data, $parameters);

                                $newItem = [
                                    'product_type_id' => $get('product_type_id'),
                                    'calculated_price' => $result['result'],
                                    'dynamic_fields_json' => [
                                        'product_name' => $data['product_name'],
                                        'purchase_type' => 'gb_product',
                                        'goldList' => $data['goldList'],
                                        'multiplier' => $multiplier,
                                        'kyat' => $data['kyat'] ?? 0,
                                        'pae' => $data['pae'] ?? 0,
                                        'yawe' => $data['yawe'] ?? 0,
                                        'kyaukWeight' => $data['kyaukWeight'] ?? 0,
                                        'goldWeightGram' => $data['goldWeightGram'] ?? 0,
                                        'percent' => blank($data['percent'] ?? null) ? 0 : $data['percent'],
                                        'reChange' => $data['reChange'] ?? '0',
                                        'is_good' => (bool) ($data['is_good'] ?? false),
                                        'quantity' => $data['quantity'] ?? 1,
                                        'remark' => $data['remark'] ?? '',
                                        'original_voucher_price' => $data['original_voucher_price'] ?? 0,
                                        'attachment_image' => $data['attachment_image'] ?? null,
                                    ]
                                ];

                                if ($livewire instanceof \App\Filament\Repurchase\Resources\PurchaseRequestResource\Pages\CreatePurchaseRequest) {
                                    $purchaseRequest = new \App\Modules\Purchase\Models\PurchaseRequest();
                                    $purchaseRequest->branch_id = $get('branch_id');
                                    $purchaseRequest->product_type_id = $get('product_type_id');
                                    $purchaseRequest->customer_name = $get('customer_name');
                                    $purchaseRequest->customer_phone = $get('customer_phone');
                                    $purchaseRequest->user_id = auth()->id();
                                    $purchaseRequest->total_amount = $result['result'];

                                    $productTypeId = $get('product_type_id');
                                    $workflow = \App\Modules\Core\Workflow\Models\Workflow::where('product_type_id', $productTypeId)
                                        ->where('is_active', true)
                                        ->first();

                                    $startState = null;
                                    if ($workflow) {
                                        $startState = $workflow->states()->where('is_start', true)->first();
                                    }

                                    if (!$startState) {
                                        $startState = \App\Modules\Core\Workflow\Models\WorkflowState::where('is_start', true)->first();
                                    }

                                    if ($startState) {
                                        $purchaseRequest->workflow_state_id = $startState->id;
                                    }

                                    $purchaseRequest->save();

                                    $purchaseItem = $purchaseRequest->items()->create($newItem);

                                    $purchaseItem->calculationHistories()->create([
                                        'parameter_snapshot_json' => $parameters,
                                        'input_snapshot_json' => $newItem['dynamic_fields_json'],
                                        'total_amount' => $result['result'],
                                        'user_id' => auth()->id(),
                                    ]);

                                    \Filament\Notifications\Notification::make()
                                        ->title('Purchase Request Auto-Created')
                                        ->body('Request saved with the first calculated item.')
                                        ->success()
                                        ->send();

                                    $livewire->redirect(self::getUrl('edit', ['record' => $purchaseRequest]));
                                    return;
                                }

                                $state = $get('items') ?? [];
                                $uuid = (string) \Illuminate\Support\Str::uuid();
                                $state[$uuid] = $newItem;
                                $set('items', $state);

                                if ($livewire instanceof \App\Filament\Repurchase\Resources\PurchaseRequestResource\Pages\EditPurchaseRequest) {
                                    $livewire->save();
                                    $livewire->redirect(static::getUrl('edit', ['record' => $livewire->record]));
                                }
                            }),

                        \Filament\Actions\Action::make('add_other_product')
                            ->hidden(fn($record) => !self::isDraft($record) && self::isAllVerified($record))
                            ->label('Other Calculator')
                            ->icon('heroicon-m-calculator')
                            ->extraAttributes(['style' => 'background-color: #ffe4e1; color: #800000; border: 1px solid #e1b4b4; font-weight: bold;'])
                            ->modalHeading('Other Product Calculator')
                            ->modalWidth('4xl')
                            ->form([
                                \Filament\Schemas\Components\Group::make([
                                    Forms\Components\TextInput::make('product_name')
                                        ->label('Product Name')
                                        ->required()
                                        ->extraInputAttributes(['onkeydown' => 'if (event.key === "Enter") { event.preventDefault(); }']),

                                    Forms\Components\Hidden::make('purchase_type')
                                        ->default('other_product'),

                                    Forms\Components\Toggle::make('is_good')->offColor('danger')->onColor('success')
                                        ->label(
                                            fn($state) => $state
                                                ? new \Illuminate\Support\HtmlString('<span style="color: green; font-weight: bold;">ရ</span>')
                                                : new \Illuminate\Support\HtmlString('<span style="color: red; font-weight: bold;">မရ</span>')
                                        )
                                        ->live()
                                        ->default(false),

                                    \Filament\Schemas\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\Select::make('goldList')
                                                ->label('Grade of Gold')
                                                ->options(self::getGoldGradeOptions('other_product'))
                                                ->required()
                                                ->live(),

                                            Forms\Components\Select::make('reChange')
                                                ->label('ပြန်ဝယ်အမျိုးအစား')
                                                ->options([
                                                    '0' => 'ဆိုင်ထည် (No)',
                                                    '1' => 'အလဲအထပ်လုပ်မယ် (Yes)',
                                                ])
                                                ->default('0')
                                                ->live(),
                                        ]),

                                    \Filament\Schemas\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\TextInput::make('kyat')
                                                ->numeric()
                                                ->label('ကျပ်')
                                                ->default(0)
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(fn(\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set) => self::convertKyatToGram($get, $set))
                                                ->extraInputAttributes(['onkeydown' => 'if (event.key === "Enter") { event.preventDefault(); }']),
                                            Forms\Components\TextInput::make('pae')
                                                ->numeric()
                                                ->label('ပဲ')
                                                ->default(0)
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(fn(\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set) => self::convertKyatToGram($get, $set))
                                                ->extraInputAttributes(['onkeydown' => 'if (event.key === "Enter") { event.preventDefault(); }']),
                                            Forms\Components\TextInput::make('yawe')
                                                ->numeric()
                                                ->label('ရွေး')
                                                ->default(0)
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(fn(\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set) => self::convertKyatToGram($get, $set))
                                                ->extraInputAttributes(['onkeydown' => 'if (event.key === "Enter") { event.preventDefault(); }']),
                                        ])
                                        ->visible(fn(\Filament\Schemas\Components\Utilities\Get $get) => (bool)$get('show_KPY')),

                                    \Filament\Schemas\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('goldWeightGram')
                                                ->numeric()
                                                ->label('Gram (g)')
                                                ->default(0)
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(fn(\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set) => self::convertGramToKyat($get, $set))
                                                ->extraInputAttributes(['onkeydown' => 'if (event.key === "Enter") { event.preventDefault(); }']),
                                            Forms\Components\TextInput::make('kyaukWeight')
                                                ->numeric()
                                                ->label('ကျောက်ချိန် (ရွေး)')
                                                ->default(0)
                                                ->live(onBlur: true)
                                                ->extraInputAttributes(['onkeydown' => 'if (event.key === "Enter") { event.preventDefault(); }']),
                                        ]),

                                    Forms\Components\Toggle::make('show_KPY')
                                        ->label('Show KPY (ကျပ်၊ ပဲ၊ ရွေး)')
                                        ->live()
                                        ->afterStateHydrated(function ($component, $state, $get) {
                                            $hasKpy = (float)($get('kyat') ?? 0) > 0 || (float)($get('pae') ?? 0) > 0 || (float)($get('yawe') ?? 0) > 0;
                                            $component->state((bool)($state || $hasKpy));
                                        }),

                                    \Filament\Schemas\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\TextInput::make('percent')
                                                ->numeric()
                                                ->label('Percent Deduction')
                                                ->suffix('%')
                                                ->default(0)
                                                ->live(onBlur: true)
                                                ->helperText(new \Illuminate\Support\HtmlString('<span class="percent-info-text">ရာခိုင်နှုန်းလျော့ထည့်ရန်</span>'))
                                                ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'ရာခိုင်နှုန်းလျော့ထည့်ရန်')
                                                ->extraInputAttributes([
                                                    'onkeydown' => 'if (event.key === "Enter") { event.preventDefault(); }',
                                                    'title' => 'ရာခိုင်နှုန်းလျော့ထည့်ရန်',
                                                ])
                                                ->extraAttributes(['class' => 'percent-field']),

                                            Forms\Components\TextInput::make('quantity')
                                                ->numeric()
                                                ->label('အရေအတွက်')
                                                ->suffix('qty')
                                                ->required()
                                                ->default(1)
                                                ->minValue(1)
                                                ->extraInputAttributes(['onkeydown' => 'if (event.key === "Enter") { event.preventDefault(); }']),
                                        ]),

                                    Forms\Components\Textarea::make('remark')
                                        ->label('Remark')
                                        ->nullable()
                                        ->rows(2)
                                        ->extraInputAttributes(['onkeydown' => 'if (event.key === "Enter") { event.preventDefault(); }']),
                                ])
                                    ->extraAttributes(['style' => 'background-color: #ffe4e1; border-radius: 8px; padding: 24px; border: 1px solid #e1b4b4;'])
                            ])
                            ->action(function (array $data, \Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $livewire) {
                                $method = \App\Modules\Core\Calculation\Models\CalculationMethod::where('name', 'Standard Jewelry Calculator')->first();
                                $parameters = $method ? $method->parameters->pluck('value', 'key')->toArray() : [];

                                $multiplier = self::resolveMultiplierValue($data['goldList'], 'other_product');
                                $data['multiplier'] = $multiplier;
                                $data['purchase_type'] = 'other_product';

                                $strategy = new \App\Modules\Core\Calculation\Strategies\JewelryCalculator();
                                $result = $strategy->calculate($data, $parameters);

                                $newItem = [
                                    'product_type_id' => $get('product_type_id'),
                                    'calculated_price' => $result['result'],
                                    'dynamic_fields_json' => [
                                        'product_name' => $data['product_name'],
                                        'purchase_type' => 'other_product',
                                        'goldList' => $data['goldList'],
                                        'multiplier' => $multiplier,
                                        'kyat' => $data['kyat'] ?? 0,
                                        'pae' => $data['pae'] ?? 0,
                                        'yawe' => $data['yawe'] ?? 0,
                                        'kyaukWeight' => $data['kyaukWeight'] ?? 0,
                                        'goldWeightGram' => $data['goldWeightGram'] ?? 0,
                                        'show_KPY' => (bool) ($data['show_KPY'] ?? false),
                                        'percent' => blank($data['percent'] ?? null) ? 0 : $data['percent'],
                                        'reChange' => $data['reChange'] ?? '0',
                                        'is_good' => (bool) ($data['is_good'] ?? false),
                                        'quantity' => $data['quantity'] ?? 1,
                                        'remark' => $data['remark'] ?? '',
                                    ]
                                ];

                                if ($livewire instanceof \App\Filament\Repurchase\Resources\PurchaseRequestResource\Pages\CreatePurchaseRequest) {
                                    $purchaseRequest = new \App\Modules\Purchase\Models\PurchaseRequest();
                                    $purchaseRequest->branch_id = $get('branch_id');
                                    $purchaseRequest->product_type_id = $get('product_type_id');
                                    $purchaseRequest->customer_name = $get('customer_name');
                                    $purchaseRequest->customer_phone = $get('customer_phone');
                                    $purchaseRequest->user_id = auth()->id();
                                    $purchaseRequest->total_amount = $result['result'];

                                    $productTypeId = $get('product_type_id');
                                    $workflow = \App\Modules\Core\Workflow\Models\Workflow::where('product_type_id', $productTypeId)
                                        ->where('is_active', true)
                                        ->first();

                                    $startState = null;
                                    if ($workflow) {
                                        $startState = $workflow->states()->where('is_start', true)->first();
                                    }

                                    if (!$startState) {
                                        $startState = \App\Modules\Core\Workflow\Models\WorkflowState::where('is_start', true)->first();
                                    }

                                    if ($startState) {
                                        $purchaseRequest->workflow_state_id = $startState->id;
                                    }

                                    $purchaseRequest->save();

                                    $purchaseItem = $purchaseRequest->items()->create($newItem);

                                    $purchaseItem->calculationHistories()->create([
                                        'parameter_snapshot_json' => $parameters,
                                        'input_snapshot_json' => $newItem['dynamic_fields_json'],
                                        'total_amount' => $result['result'],
                                        'user_id' => auth()->id(),
                                    ]);

                                    \Filament\Notifications\Notification::make()
                                        ->title('Purchase Request Auto-Created')
                                        ->body('Request saved with the first calculated item.')
                                        ->success()
                                        ->send();

                                    $livewire->redirect(self::getUrl('edit', ['record' => $purchaseRequest]));
                                    return;
                                }

                                $state = $get('items') ?? [];
                                $uuid = (string) \Illuminate\Support\Str::uuid();
                                $state[$uuid] = $newItem;
                                $set('items', $state);

                                if ($livewire instanceof \App\Filament\Repurchase\Resources\PurchaseRequestResource\Pages\EditPurchaseRequest) {
                                    $livewire->save();
                                    $livewire->redirect(static::getUrl('edit', ['record' => $livewire->record]));
                                }
                            }),
                    ])
                    ->schema([
                        Forms\Components\Placeholder::make('calculator_table_styles')
                            ->label('')
                            ->hiddenLabel()
                            ->content(new \Illuminate\Support\HtmlString('
                                <style>
                                    .calculator-table-repeater > table {
                                        display: table !important;
                                        width: 100% !important;
                                    }
                                    .calculator-table-repeater > table > thead {
                                        display: table-header-group !important;
                                    }
                                    .calculator-table-repeater > table > tbody {
                                        display: table-row-group !important;
                                    }
                                    .calculator-table-repeater > table > tbody > tr {
                                        display: table-row !important;
                                        padding: 0 !important;
                                    }
                                    .calculator-table-repeater > table > tbody > tr > td {
                                        display: table-cell !important;
                                        padding: 0.5rem 0.75rem !important;
                                        vertical-align: middle !important;
                                    }
                                    .calculator-table-repeater > table > thead > tr > th {
                                        display: table-cell !important;
                                        padding: 0.5rem 0.75rem !important;
                                        vertical-align: middle !important;
                                    }
                                    .calculator-table-repeater .fi-fo-field-label-content,
                                    .calculator-table-repeater .fi-in-entry-label {
                                        display: none !important;
                                    }
                                    .calculator-table-repeater > table > tbody > tr > td.fi-hidden {
                                        display: table-cell !important;
                                    }
                                    .calculator-table-repeater .fi-fo-table-repeater-actions {
                                        padding: 0.5rem 0.75rem !important;
                                    }
                                    .custom-badge {
                                        display: inline-flex !important;
                                        align-items: center !important;
                                        padding: 0.25rem 0.75rem !important;
                                        font-size: 0.75rem !important;
                                        font-weight: 700 !important;
                                        text-transform: uppercase !important;
                                        letter-spacing: 0.05em !important;
                                        border-radius: 9999px !important;
                                        white-space: nowrap !important;
                                    }
                                    .custom-badge-gray {
                                        background-color: #f3f4f6 !important;
                                        color: #1f2937 !important;
                                        border: 1px solid #d1d5db !important;
                                    }
                                    .dark .custom-badge-gray {
                                        background-color: #374151 !important;
                                        color: #f3f4f6 !important;
                                        border: 1px solid #4b5563 !important;
                                    }
                                    .custom-badge-red {
                                        background-color: #fee2e2 !important;
                                        color: #991b1b !important;
                                        border: 1px solid #fecaca !important;
                                    }
                                    .dark .custom-badge-red {
                                        background-color: rgba(153, 27, 27, 0.4) !important;
                                        color: #fecaca !important;
                                        border: 1px solid rgba(239, 68, 68, 0.4) !important;
                                    }
                                    .custom-badge-green {
                                        background-color: #d1fae5 !important;
                                        color: #065f46 !important;
                                        border: 1px solid #a7f3d0 !important;
                                    }
                                    .dark .custom-badge-green {
                                        background-color: rgba(6, 95, 70, 0.4) !important;
                                        color: #a7f3d0 !important;
                                        border: 1px solid rgba(16, 185, 129, 0.4) !important;
                                    }
                                    .percent-info-text {
                                        display: none;
                                        color: #ef4444 !important;
                                        font-size: 0.8rem;
                                        margin-top: 4px;
                                        font-weight: 600;
                                    }
                                    .percent-field:focus-within .percent-info-text {
                                        display: block !important;
                                    }
                                </style>
                            ')),
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->addable(false) // Disable inline create button
                            ->deletable(false)
                            ->extraAttributes([
                                'class' => 'overflow-x-auto block w-full max-w-full min-w-0 calculator-table-repeater',
                                'style' => 'overflow-x: auto; display: block; max-width: 100%; min-width: 0;',
                            ])
                            ->schema([
                                Forms\Components\Placeholder::make('product_type')
                                    ->label('Product Type')
                                    ->content(function (\Filament\Schemas\Components\Utilities\Get $get) {
                                        $typeId = $get('product_type_id');
                                        if (! $typeId) return '-';
                                        $productType = \App\Models\ProductType::find($typeId);
                                        return $productType ? $productType->name : '-';
                                    }),

                                Forms\Components\Placeholder::make('product_name')
                                    ->label('Product Name')
                                    ->content(function (\Filament\Schemas\Components\Utilities\Get $get) {
                                        $name = $get('dynamic_fields_json.product_name') ?? '-';
                                        $qty = $get('dynamic_fields_json.quantity') ?? 1;
                                        $remark = $get('dynamic_fields_json.remark');

                                        $html = "<strong>" . e($name) . "</strong>";
                                        if ($qty > 1) {
                                            $html .= " <span class='text-gray-500 text-xs'>(Qty: {$qty})</span>";
                                        }
                                        $reChange = $get('dynamic_fields_json.reChange');
                                        if ((string)$reChange === '2') {
                                            $origPrice = $get('dynamic_fields_json.original_voucher_price') ?? 0;
                                            $html .= "<br/><span class='text-teal-600 dark:text-teal-400 text-xs font-semibold'>Percent Buyback: " . number_format($origPrice) . " MMK</span>";
                                        }
                                        if ($remark) {
                                            $html .= "<br/><span class='text-gray-400 text-xs italic'>" . e($remark) . "</span>";
                                        }
                                        return new \Illuminate\Support\HtmlString($html);
                                    }),

                                Forms\Components\Placeholder::make('gold_grade')
                                    ->label('Gold Grade')
                                    ->content(function (\Filament\Schemas\Components\Utilities\Get $get) {
                                        $grade = $get('dynamic_fields_json.goldList');
                                        if (! $grade) return '-';
                                        return $grade . ' ပဲ';
                                    }),

                                Forms\Components\Placeholder::make('weight')
                                    ->label('(g)')
                                    ->content(fn(\Filament\Schemas\Components\Utilities\Get $get) => $get('dynamic_fields_json.goldWeightGram') ?? '-'),

                                Forms\Components\Placeholder::make('kyauk_weight')
                                    ->label('ကျောက်')
                                    ->content(fn(\Filament\Schemas\Components\Utilities\Get $get) => $get('dynamic_fields_json.kyaukWeight') ?? '-'),

                                Forms\Components\Placeholder::make('deduction')
                                    ->label('(%)')
                                    ->content(fn(\Filament\Schemas\Components\Utilities\Get $get) => ($get('dynamic_fields_json.percent') ?? '0') . '%'),

                                Forms\Components\Placeholder::make('is_good')
                                    ->label('ရ/မရ')
                                    ->content(function (\Filament\Schemas\Components\Utilities\Get $get) {
                                        $isGood = $get('dynamic_fields_json.is_good') ?? false;
                                        return $isGood
                                            ? new \Illuminate\Support\HtmlString('<span class="custom-badge custom-badge-green">ရ</span>')
                                            : new \Illuminate\Support\HtmlString('<span class="custom-badge custom-badge-red">မရ</span>');
                                    }),

                                Forms\Components\Placeholder::make('validation_status')
                                    ->label('Verification')
                                    ->content(function ($record) {
                                        if (!$record || !$record->exists) {
                                            return '-';
                                        }
                                        $histories = $record->validationHistories;
                                        if ($histories->isEmpty()) {
                                            return new \Illuminate\Support\HtmlString('<span class="custom-badge custom-badge-gray">Pending</span>');
                                        }

                                        $passed = $histories->where('status', 'PASS')->count();
                                        $failed = $histories->where('status', 'FAIL')->count();

                                        if ($failed > 0) {
                                            return new \Illuminate\Support\HtmlString("<span class=\"custom-badge custom-badge-red\">{$passed} Pass / {$failed} Fail</span>");
                                        }
                                        return new \Illuminate\Support\HtmlString("<span class=\"custom-badge custom-badge-green\">{$passed} Pass</span>");
                                    }),

                                Forms\Components\Placeholder::make('actual_price')
                                    ->label('Actual Price')
                                    ->content(fn(\Filament\Schemas\Components\Utilities\Get $get) => number_format($get('calculated_price') ?? 0) . ' MMK'),

                                \Filament\Schemas\Components\Actions::make([
                                    \Filament\Actions\Action::make('edit_item')
                                        ->label('Edit')
                                        ->icon('heroicon-m-pencil-square')
                                        ->color('primary')
                                        ->hidden(fn($record) => !self::isDraft($record?->purchaseRequest) && self::isAllVerified($record?->purchaseRequest))
                                        ->modalHeading('Edit Product')
                                        ->modalWidth('4xl')
                                        ->form(function (\Filament\Schemas\Components\Utilities\Get $get) {
                                            $purchaseType = $get('dynamic_fields_json.purchase_type') ?? 'gb_product';
                                            $bgColor = $purchaseType === 'other_product' ? '#ffe4e1' : '#fffff0';
                                            $borderColor = $purchaseType === 'other_product' ? '#e1b4b4' : '#d1d1c4';

                                            return [
                                                \Filament\Schemas\Components\Group::make([
                                                    Forms\Components\TextInput::make('product_name')
                                                        ->label('Product Name')
                                                        ->required()
                                                        ->extraInputAttributes(['onkeydown' => 'if (event.key === "Enter") { event.preventDefault(); }']),

                                                    Forms\Components\Hidden::make('purchase_type')
                                                        ->default($purchaseType),

                                                    Forms\Components\Toggle::make('is_good')->offColor('danger')->onColor('success')
                                                        ->label(
                                                            fn($state) => $state
                                                                ? new \Illuminate\Support\HtmlString('<span style="color: green; font-weight: bold;">ရ</span>')
                                                                : new \Illuminate\Support\HtmlString('<span style="color: red; font-weight: bold;">မရ</span>')
                                                        )
                                                        ->live()
                                                        ->default(false),

                                                    \Filament\Schemas\Components\Grid::make(2)
                                                        ->schema([
                                                            Forms\Components\Select::make('goldList')
                                                                ->label('Grade of Gold')
                                                                ->options(self::getGoldGradeOptions($purchaseType))
                                                                ->required()
                                                                ->live(),

                                                            Forms\Components\Select::make('reChange')
                                                                ->label('ပြန်ဝယ်အမျိုးအစား')
                                                                ->options(fn() => $purchaseType === 'gb_product' ? [
                                                                    '0' => 'ဆိုင်ထည် (No)',
                                                                    '1' => 'အလဲအထပ်လုပ်မယ် (Yes)',
                                                                    '2' => 'Percent ထည်ပြန်ဝယ်',
                                                                ] : [
                                                                    '0' => 'ဆိုင်ထည် (No)',
                                                                    '1' => 'အလဲအထပ်လုပ်မယ် (Yes)',
                                                                ])
                                                                ->default('0')
                                                                ->live(),
                                                        ]),

                                                    Forms\Components\TextInput::make('original_voucher_price')
                                                        ->numeric()
                                                        ->label('Original Voucher Price')
                                                        ->required(fn(\Filament\Schemas\Components\Utilities\Get $get) => (string)$get('reChange') === '2')
                                                        ->visible(fn(\Filament\Schemas\Components\Utilities\Get $get) => (string)$get('reChange') === '2')
                                                        ->live(onBlur: true)
                                                        ->extraInputAttributes(['onkeydown' => 'if (event.key === "Enter") { event.preventDefault(); }']),

                                                    Forms\Components\FileUpload::make('attachment_image')
                                                        ->label('Attachment Image')
                                                        ->image()
                                                        ->disk('public')
                                                        ->visibility('public')
                                                        ->directory('attachments/purchase_items')
                                                        ->required(fn(\Filament\Schemas\Components\Utilities\Get $get) => (string)$get('reChange') === '2')
                                                        ->visible(fn(\Filament\Schemas\Components\Utilities\Get $get) => (string)$get('reChange') === '2'),

                                                    \Filament\Schemas\Components\Grid::make(1)
                                                        ->schema([
                                                            Forms\Components\TextInput::make('goldWeightGram')
                                                                ->numeric()
                                                                ->label('Gram (g)')
                                                                ->default(0)
                                                                ->live(onBlur: true)
                                                                ->afterStateUpdated(fn(\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set) => self::convertGramToKyat($get, $set))
                                                                ->extraInputAttributes(['onkeydown' => 'if (event.key === "Enter") { event.preventDefault(); }']),
                                                            Forms\Components\TextInput::make('kyaukWeight')
                                                                ->numeric()
                                                                ->label('ကျောက်ချိန် (ရွေး)')
                                                                ->default(0)
                                                                ->live(onBlur: true)
                                                                ->extraInputAttributes(['onkeydown' => 'if (event.key === "Enter") { event.preventDefault(); }']),
                                                        ]),

                                                    Forms\Components\Toggle::make('show_KPY')
                                                        ->label('Show KPY (ကျပ်၊ ပဲ၊ ရွေး)')
                                                        ->live()
                                                        ->afterStateHydrated(function ($component, $state, $get) {
                                                            $hasKpy = (float)($get('kyat') ?? 0) > 0 || (float)($get('pae') ?? 0) > 0 || (float)($get('yawe') ?? 0) > 0;
                                                            $component->state((bool)($state || $hasKpy));
                                                        }),

                                                    \Filament\Schemas\Components\Grid::make(3)
                                                        ->schema([
                                                            Forms\Components\TextInput::make('kyat')
                                                                ->numeric()
                                                                ->label('ကျပ်')
                                                                ->default(0)
                                                                ->live(onBlur: true)
                                                                ->afterStateUpdated(fn(\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set) => self::convertKyatToGram($get, $set))
                                                                ->extraInputAttributes(['onkeydown' => 'if (event.key === "Enter") { event.preventDefault(); }']),
                                                            Forms\Components\TextInput::make('pae')
                                                                ->numeric()
                                                                ->label('ပဲ')
                                                                ->default(0)
                                                                ->live(onBlur: true)
                                                                ->afterStateUpdated(fn(\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set) => self::convertKyatToGram($get, $set))
                                                                ->extraInputAttributes(['onkeydown' => 'if (event.key === "Enter") { event.preventDefault(); }']),
                                                            Forms\Components\TextInput::make('yawe')
                                                                ->numeric()
                                                                ->label('ရွေး')
                                                                ->default(0)
                                                                ->live(onBlur: true)
                                                                ->afterStateUpdated(fn(\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set) => self::convertKyatToGram($get, $set))
                                                                ->extraInputAttributes(['onkeydown' => 'if (event.key === "Enter") { event.preventDefault(); }']),
                                                        ])
                                                        ->visible(fn(\Filament\Schemas\Components\Utilities\Get $get) => (bool)$get('show_KPY')),

                                                    \Filament\Schemas\Components\Grid::make(3)
                                                        ->schema([
                                                            Forms\Components\TextInput::make('percent')
                                                                ->numeric()
                                                                ->label('Percent Deduction')
                                                                ->suffix('%')
                                                                ->default(0)
                                                                ->required(fn(\Filament\Schemas\Components\Utilities\Get $get) => (string)$get('reChange') === '2')
                                                                ->live(onBlur: true)
                                                                ->helperText(new \Illuminate\Support\HtmlString('<span class="percent-info-text">ရာခိုင်နှုန်းလျော့ထည့်ရန်</span>'))
                                                                ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'ရာခိုင်နှုန်းလျော့ထည့်ရန်')
                                                                ->extraInputAttributes([
                                                                    'onkeydown' => 'if (event.key === "Enter") { event.preventDefault(); }',
                                                                    'title' => 'ရာခိုင်နှုန်းလျော့ထည့်ရန်',
                                                                ])
                                                                ->extraAttributes(['class' => 'percent-field']),

                                                            Forms\Components\TextInput::make('quantity')
                                                                ->numeric()
                                                                ->label('အရေအတွက်')
                                                                ->suffix('qty')
                                                                ->required()
                                                                ->default(1)
                                                                ->minValue(1)
                                                                ->extraInputAttributes(['onkeydown' => 'if (event.key === "Enter") { event.preventDefault(); }']),
                                                        ]),

                                                    Forms\Components\Textarea::make('remark')
                                                        ->label('Remark')
                                                        ->nullable()
                                                        ->rows(2)
                                                        ->extraInputAttributes(['onkeydown' => 'if (event.key === "Enter") { event.preventDefault(); }']),
                                                ])
                                                    ->extraAttributes(['style' => "background-color: {$bgColor}; border-radius: 8px; padding: 24px; border: 1px solid {$borderColor};"])
                                            ];
                                        })
                                        ->action(function (array $data, \Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $livewire, $record) {
                                            // Calculate the price!
                                            $method = \App\Modules\Core\Calculation\Models\CalculationMethod::where('name', 'Standard Jewelry Calculator')->first();
                                            $parameters = $method ? $method->parameters->pluck('value', 'key')->toArray() : [];

                                            $multiplier = self::resolveMultiplierValue($data['goldList'], $data['purchase_type'] ?? 'gb_product');
                                            $data['multiplier'] = $multiplier;

                                            $strategy = new \App\Modules\Core\Calculation\Strategies\JewelryCalculator();
                                            $result = $strategy->calculate($data, $parameters);

                                            $updatedFields = [
                                                'product_name' => $data['product_name'],
                                                'purchase_type' => $data['purchase_type'] ?? 'gb_product',
                                                'goldList' => $data['goldList'],
                                                'multiplier' => $multiplier,
                                                'kyat' => $data['kyat'] ?? 0,
                                                'pae' => $data['pae'] ?? 0,
                                                'yawe' => $data['yawe'] ?? 0,
                                                'kyaukWeight' => $data['kyaukWeight'] ?? 0,
                                                'goldWeightGram' => $data['goldWeightGram'] ?? 0,
                                                'show_KPY' => (bool) ($data['show_KPY'] ?? false),
                                                'percent' => blank($data['percent'] ?? null) ? 0 : $data['percent'],
                                                'reChange' => $data['reChange'] ?? '0',
                                                'is_good' => (bool) ($data['is_good'] ?? false),
                                                'quantity' => $data['quantity'] ?? 1,
                                                'remark' => $data['remark'] ?? '',
                                                'original_voucher_price' => $data['original_voucher_price'] ?? 0,
                                                'attachment_image' => $data['attachment_image'] ?? null,
                                            ];

                                            // Update state on the row
                                            $set('product_type_id', $get('../../product_type_id'));
                                            $set('calculated_price', $result['result']);
                                            $set('dynamic_fields_json', $updatedFields);

                                            if ($record) {
                                                $record->update([
                                                    'calculated_price' => $result['result'],
                                                    'dynamic_fields_json' => $updatedFields,
                                                ]);
                                            }

                                            \Filament\Notifications\Notification::make()
                                                ->title('Item updated successfully')
                                                ->success()
                                                ->send();

                                             if ($livewire instanceof \App\Filament\Repurchase\Resources\PurchaseRequestResource\Pages\EditPurchaseRequest) {
                                                 $livewire->save();
                                                 $livewire->redirect(static::getUrl('edit', ['record' => $livewire->record]));
                                             }
                                        })
                                        ->mountUsing(function (\Filament\Schemas\Schema $form, \Filament\Schemas\Components\Utilities\Get $get) {
                                            $state = $get('dynamic_fields_json') ?? [];
                                            $form->fill($state);
                                        }),
                                    \Filament\Actions\Action::make('view_attachment')
                                        ->label('')
                                        ->tooltip('View Attachment')
                                        ->icon('heroicon-m-photo')
                                        ->color('danger')
                                        ->modalHeading('Attachment Image')
                                        ->modalContent(fn($record) => new \Illuminate\Support\HtmlString(
                                            $record && isset($record->dynamic_fields_json['attachment_image'])
                                                ? '<div class="flex justify-center"><img src="' . asset('storage/' . $record->dynamic_fields_json['attachment_image']) . '" class="max-w-full h-auto rounded-lg shadow-md border border-gray-200 dark:border-gray-800" /></div>'
                                                : '<p class="text-gray-500 text-center">No attachment image uploaded.</p>'
                                        ))
                                        ->modalSubmitAction(false)
                                        ->modalCancelActionLabel('Close')
                                        ->visible(fn($record) => $record && !empty($record->dynamic_fields_json['attachment_image'])),

                                    \Filament\Actions\Action::make('delete_item')
                                        ->label('')
                                        ->tooltip('Delete')
                                        ->icon('heroicon-m-trash')
                                        ->color('danger')
                                        ->requiresConfirmation()
                                        ->modalHeading('Delete Item')
                                        ->modalDescription('Are you sure you want to delete this item? This action cannot be undone.')
                                        ->modalSubmitActionLabel('Yes, delete it')
                                        ->hidden(fn($record) => !self::isDraft($record?->purchaseRequest) && self::isAllVerified($record?->purchaseRequest))
                                        ->action(function ($record, $livewire) {
                                            if ($record) {
                                                $record->delete();
                                            }
                                            if ($livewire instanceof \App\Filament\Repurchase\Resources\PurchaseRequestResource\Pages\EditPurchaseRequest) {
                                                $livewire->save();
                                                $livewire->redirect(static::getUrl('edit', ['record' => $livewire->record]));
                                            }
                                        }),

                                    \Filament\Actions\Action::make('view_validation_history')
                                        ->label('History')
                                        ->icon('heroicon-m-clock')
                                        ->color('gray')
                                        ->modalHeading('Verification History')
                                        ->modalSubmitAction(false)
                                        ->modalCancelActionLabel('Close')
                                        ->visible(fn($record) => $record && $record->exists)
                                        ->form(function ($record) {
                                            $histories = $record->validationHistories()->with('rule', 'user')->latest()->get();
                                            $schema = [];

                                            if ($record) {
                                                $inputs = $record->dynamic_fields_json ?? [];
                                                $qty = $inputs['quantity'] ?? 1;
                                                $rem = $inputs['remark'] ?? '-';

                                                $schema[] = \Filament\Schemas\Components\Section::make('Item Specification')
                                                    ->schema([
                                                        \Filament\Schemas\Components\Grid::make(2)
                                                            ->schema([
                                                                Forms\Components\Placeholder::make('item_qty')
                                                                    ->label('Quantity')
                                                                    ->content($qty),
                                                                Forms\Components\Placeholder::make('item_remark')
                                                                    ->label('Calculator Remark')
                                                                    ->content($rem ?: '-'),
                                                            ])
                                                    ]);
                                            }
                                            if ($histories->isEmpty()) {
                                                $schema[] = Forms\Components\Placeholder::make('no_history')
                                                    ->label('')
                                                    ->content('No verification history recorded yet.');
                                            } else {
                                                foreach ($histories as $index => $history) {
                                                    $schema[] = \Filament\Schemas\Components\Section::make("Verification Check: " . ($history->rule?->label ?? $history->rule?->field_name ?? 'Custom Rule'))
                                                        ->description("Checked on " . $history->created_at->toDateTimeString() . " by " . ($history->user?->name ?? 'System'))
                                                        ->schema([
                                                            \Filament\Schemas\Components\Grid::make(4)
                                                                ->schema([
                                                                    Forms\Components\Placeholder::make("status_{$index}")
                                                                        ->label('Status')
                                                                        ->content(new \Illuminate\Support\HtmlString(
                                                                            $history->status === 'PASS'
                                                                                ? '<span class="text-success-600 font-semibold">Correct (Pass)</span>'
                                                                                : '<span class="text-danger-600 font-semibold">Fail</span>'
                                                                        )),
                                                                    Forms\Components\Placeholder::make("input_{$index}")
                                                                        ->label('Input Value')
                                                                        ->content($history->input_value ?? 'null'),
                                                                    Forms\Components\Placeholder::make("expected_{$index}")
                                                                        ->label('Expected Value')
                                                                        ->content($history->expected_value ?? 'null'),
                                                                    Forms\Components\Placeholder::make("remarks_{$index}")
                                                                        ->label('Remarks')
                                                                        ->content($history->remarks ?? '-'),
                                                                ])
                                                        ]);
                                                }
                                            }
                                            return $schema;
                                        }),
                                ]),

                                Forms\Components\Hidden::make('product_type_id'),
                                Forms\Components\Hidden::make('calculated_price'),
                                Forms\Components\Hidden::make('dynamic_fields_json'),
                            ])
                            ->table([
                                \Filament\Forms\Components\Repeater\TableColumn::make('Product Type'),
                                \Filament\Forms\Components\Repeater\TableColumn::make('Product Name'),
                                \Filament\Forms\Components\Repeater\TableColumn::make('Gold Grade'),
                                \Filament\Forms\Components\Repeater\TableColumn::make('(Gram)'),
                                \Filament\Forms\Components\Repeater\TableColumn::make('ကျောက်'),
                                \Filament\Forms\Components\Repeater\TableColumn::make('(%)'),
                                \Filament\Forms\Components\Repeater\TableColumn::make('ရ/မရ'),
                                \Filament\Forms\Components\Repeater\TableColumn::make('Verification'),
                                \Filament\Forms\Components\Repeater\TableColumn::make('Actual Price'),
                                \Filament\Forms\Components\Repeater\TableColumn::make('Actions'),
                            ])
                            ->afterCreate(function (\Illuminate\Database\Eloquent\Model $record, array $data) {
                                self::saveCalculationHistory($record, $data);
                            })
                            ->afterUpdate(function (\Illuminate\Database\Eloquent\Model $record, array $data) {
                                self::saveCalculationHistory($record, $data);
                            })
                            ->itemLabel(fn(array $state): ?string => 'Purchase Item: ' . ($state['calculated_price'] ?? '0') . ' MMK'),
                    ]),
            ]);
    }

    public static function getConversionRate(): float
    {
        $method = \App\Modules\Core\Calculation\Models\CalculationMethod::where('name', 'Standard Jewelry Calculator')->first();
        $parameters = $method ? $method->parameters->pluck('value', 'key')->toArray() : [];
        return (float) ($parameters['gram_per_kyat'] ?? 16.606);
    }

    public static function convertKyatToGram(\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set): void
    {
        $kyat = (float) ($get('kyat') ?? 0);
        $pae = (float) ($get('pae') ?? 0);
        $yawe = (float) ($get('yawe') ?? 0);

        $rate = self::getConversionRate();
        $totalKyat = $kyat + ($pae / 16) + ($yawe / 128);
        $gram = $totalKyat * $rate;

        $set('goldWeightGram', round($gram, 4));
    }

    public static function convertGramToKyat(\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set): void
    {
        $gram = (float) ($get('goldWeightGram') ?? 0);
        $rate = self::getConversionRate();

        if ($rate <= 0) return;

        $totalKyat = $gram / $rate;
        $kyat = floor($totalKyat);
        $remainingKyat = $totalKyat - $kyat;

        $totalPae = $remainingKyat * 16;
        $pae = floor($totalPae);
        $remainingPae = $totalPae - $pae;

        $yawe = $remainingPae * 8;

        $set('kyat', $kyat);
        $set('pae', $pae);
        $set('yawe', round($yawe, 4));
    }

    public static function getGoldGradeOptions($purchaseType): array
    {
        if ($purchaseType === 'other_product') {
            return [
                '16' => '16 ပဲ (99.5%)',
                '15' => '15 ပဲ',
                '14.2' => 'ဒင်္ဂါး (14.2)',
                '14' => '14 ပဲ',
                '13' => '13 ပဲ (840)',
                '12' => '12 ပဲ (750)',
                '10' => '10 ပဲ',
                '8' => '8 ပဲ',
                '4' => '4 ပဲ',
            ];
        }
        return [
            '16' => '16 ပဲ (999)',
            '15' => '15 ပဲ',
            '14.2' => 'ဒင်္ဂါး (14.2)',
            '14' => '14 ပဲ',
            '13' => '13 ပဲ (840)',
            '12' => '12 ပဲ (750)',
        ];
    }

    public static function resolveMultiplierValue($goldList, $purchaseType): float
    {
        $defaultMultipliers = [
            'gb_product' => [
                '16' => 1.0,
                '15' => round(16 / 17, 6),
                '14.2' => round(128 / 140, 6),
                '142' => round(128 / 140, 6),
                '14' => round(16 / 18, 6),
                '13' => 0.7522,
                '12' => 0.75,
            ],
            'other_product' => [
                '16' => 0.954,
                '15' => 0.8962,
                '14.2' => 0.8693,
                '142' => 0.8693,
                '14' => 0.8439,
                '13' => round((15.35 / 332.12) * 16.606, 6),
                '12' => round((14.1 / 332.12) * 16.606, 6),
                '10' => round((11.6 / 332.12) * 16.606, 6),
                '8' => round((9.1 / 332.12) * 16.606, 6),
                '4' => round((4.1 / 332.12) * 16.606, 6),
            ]
        ];

        $method = \App\Modules\Core\Calculation\Models\CalculationMethod::where('name', 'Standard Jewelry Calculator')->first();
        $parameters = $method ? $method->parameters->pluck('value', 'key')->toArray() : [];
        $gramPerKyat = $parameters['gram_per_kyat'] ?? 16.606;

        if ($purchaseType === 'other_product' && isset($parameters['gram_per_kyat'])) {
            $defaultMultipliers['other_product']['13'] = round((15.35 / 332.12) * $gramPerKyat, 6);
            $defaultMultipliers['other_product']['12'] = round((14.1 / 332.12) * $gramPerKyat, 6);
            $defaultMultipliers['other_product']['10'] = round((11.6 / 332.12) * $gramPerKyat, 6);
            $defaultMultipliers['other_product']['8'] = round((9.1 / 332.12) * $gramPerKyat, 6);
            $defaultMultipliers['other_product']['4'] = round((4.1 / 332.12) * $gramPerKyat, 6);
        }

        $multiplierPrefix = $purchaseType === 'other_product' ? 'multiplier_oth_' : 'multiplier_gb_';
        $multiplierKey = $multiplierPrefix . $goldList;
        if ($goldList == '142') {
            $multiplierKey = $multiplierPrefix . '14.2';
        }

        return isset($parameters[$multiplierKey])
            ? (float) $parameters[$multiplierKey]
            : ($defaultMultipliers[$purchaseType][(string) $goldList] ?? 0.0);
    }

    public static function updateCalculatedPrice(\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set): void
    {
        $inputs = $get('dynamic_fields_json') ?? [];

        $method = \App\Modules\Core\Calculation\Models\CalculationMethod::where('name', 'Standard Jewelry Calculator')->first();
        $parameters = $method ? $method->parameters->pluck('value', 'key')->toArray() : [];

        $strategy = new \App\Modules\Core\Calculation\Strategies\JewelryCalculator();

        $inputs = array_merge([
            'purchase_type' => 'gb_product',
            'goldList' => '16',
            'multiplier' => 1.0,
            'reChange' => '0',
            'kyat' => 0,
            'pae' => 0,
            'yawe' => 0,
            'kyaukWeight' => 0,
            'goldWeightGram' => 0,
            'percent' => 0,
            'quantity' => 1,
            'remark' => '',
        ], $inputs);

        $result = $strategy->calculate($inputs, $parameters);

        $set('calculated_price', $result['result']);
    }

    public static function saveCalculationHistory(\Illuminate\Database\Eloquent\Model $record, array $data): void
    {
        if ($record->wasRecentlyCreated || $record->wasChanged(['calculated_price', 'dynamic_fields_json'])) {
            $method = \App\Modules\Core\Calculation\Models\CalculationMethod::where('name', 'Standard Jewelry Calculator')->first();
            $parameters = $method ? $method->parameters->pluck('value', 'key')->toArray() : [];

            $record->calculationHistories()->create([
                'parameter_snapshot_json' => $parameters,
                'input_snapshot_json' => $data['dynamic_fields_json'] ?? [],
                'total_amount' => $data['calculated_price'] ?? 0,
                'user_id' => auth()->id(),
            ]);
        }
    }

    public static function isDraft($record): bool
    {
        if (!$record || !$record->exists) {
            return true;
        }
        $stateName = $record->workflowState?->name;
        return !$stateName || strtolower($stateName) === 'draft';
    }

    public static function isAllVerified($record): bool
    {
        if (!$record || !$record->exists) {
            return false;
        }

        // If workflow state is already Verified or Paid, block modifications
        $stateName = $record->workflowState?->name;
        if (in_array($stateName, ['Verified', 'Paid'])) {
            return true;
        }

        if ($record->items->isEmpty()) {
            return false;
        }

        // Resolve validation rule set
        $productTypeId = $record->product_type_id;
        $workflow = \App\Modules\Core\Workflow\Models\Workflow::where('product_type_id', $productTypeId)
            ->where('is_active', true)
            ->first();

        $ruleSet = null;
        if ($workflow) {
            $transition = \App\Modules\Core\Workflow\Models\WorkflowTransition::where('workflow_id', $workflow->id)
                ->whereHas('fromState', fn($q) => $q->where('name', 'Submitted'))
                ->whereHas('toState', fn($q) => $q->where('name', 'Verified'))
                ->first();
            if ($transition && $transition->validation_rule_set_id) {
                if (!$transition->block_on_fail) {
                    return true;
                }
                $ruleSet = \App\Modules\Core\Validation\Models\ValidationRuleSet::find($transition->validation_rule_set_id);
            }
        }

        if (!$ruleSet) {
            $ruleSet = \App\Modules\Core\Validation\Models\ValidationRuleSet::first();
        }

        if (!$ruleSet || $ruleSet->rules->isEmpty()) {
            return false;
        }

        $rulesCount = $ruleSet->rules->count();

        // Check if every item has validation histories with status = 'PASS' matching the rule set
        foreach ($record->items as $item) {
            $passedCount = $item->validationHistories()->where('status', 'PASS')->count();
            if ($passedCount < $rulesCount) {
                return false;
            }
        }

        return true;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('purchase_number')
                    ->label('Purchase No')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $search = trim($search);
                        
                        // 1. Full pattern: PR-BRANCHCODE/YYMMDDNNN
                        if (preg_match('/^PR-([A-Z0-9]+)\/(\d{6})(\d{3})$/i', $search, $matches)) {
                            $branchCode = $matches[1];
                            $dateStr = $matches[2];
                            $seq = intval($matches[3]);
                            
                            try {
                                $date = \Carbon\Carbon::createFromFormat('ymd', $dateStr)->toDateString();
                                
                                $id = \App\Modules\Purchase\Models\PurchaseRequest::whereDate('created_at', $date)
                                    ->whereHas('branch', fn($q) => $q->where('code', $branchCode))
                                    ->orderBy('id', 'asc')
                                    ->skip($seq - 1)
                                    ->take(1)
                                    ->value('id');
                                
                                if ($id) {
                                    return $query->where('id', $id);
                                }
                            } catch (\Exception $e) {
                            }
                        }
                        
                        // 2. Date only: YYMMDD
                        if (preg_match('/^\d{6}$/', $search)) {
                            try {
                                $date = \Carbon\Carbon::createFromFormat('ymd', $search)->toDateString();
                                return $query->whereDate('created_at', $date);
                            } catch (\Exception $e) {
                            }
                        }

                        // 3. Branch only starting with PR-: PR-BRANCHCODE
                        if (preg_match('/^PR-([A-Z0-9]+)$/i', $search, $matches)) {
                            $branchCode = $matches[1];
                            return $query->whereHas('branch', fn($q) => $q->where('code', 'like', "%{$branchCode}%"));
                        }

                        // 4. Default fallback: search branch code or raw ID
                        return $query->where(function (Builder $q) use ($search) {
                            $q->whereHas('branch', fn($sub) => $sub->where('code', 'like', "%{$search}%"))
                              ->orWhere('id', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('created_at', $direction)
                                     ->orderBy('id', $direction);
                    }),
                Tables\Columns\TextColumn::make('branch.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('productType.name')
                    ->label('Product Type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('workflowState.name')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchaseDecision.status')
                    ->label('Decision Status')
                    ->badge()
                    ->color(fn($state): string => match ($state) {
                        'open' => 'warning',
                        'closed' => 'success',
                        default => 'gray',
                    })
                    ->placeholder('None')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->options(\App\Models\Branch::pluck('name', 'id'))
                    ->default(fn() => auth()->user()?->branch_id),
                Tables\Filters\SelectFilter::make('workflow_state_id')
                    ->label('Status')
                    ->relationship('workflowState', 'name'),
                Tables\Filters\SelectFilter::make('payment_workflow_status')
                    ->label('Workflow State Group')
                    ->options([
                        'before_paid' => 'Before Paid',
                        'paid_and_after' => 'Paid & After',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }
                        
                        $paidStateIds = \App\Modules\Core\Workflow\Models\WorkflowState::where('name', 'Paid')->pluck('id')->toArray();
                        $afterPaidStateIds = [];
                        $queue = $paidStateIds;
                        
                        while (!empty($queue)) {
                            $currentStateId = array_shift($queue);
                            $nextStateIds = \App\Modules\Core\Workflow\Models\WorkflowTransition::where('from_state_id', $currentStateId)
                                ->pluck('to_state_id')
                                ->toArray();
                                
                            foreach ($nextStateIds as $nextStateId) {
                                if (!in_array($nextStateId, $paidStateIds) && !in_array($nextStateId, $afterPaidStateIds)) {
                                    $afterPaidStateIds[] = $nextStateId;
                                    $queue[] = $nextStateId;
                                }
                            }
                        }
                        
                        if ($data['value'] === 'paid_and_after') {
                            return $query->whereIn('workflow_state_id', array_merge($paidStateIds, $afterPaidStateIds));
                        } elseif ($data['value'] === 'before_paid') {
                            return $query->whereNotIn('workflow_state_id', array_merge($paidStateIds, $afterPaidStateIds));
                        }
                        
                        return $query;
                    }),
                Tables\Filters\SelectFilter::make('decision_status')
                    ->label('Decision Status')
                    ->options([
                        'open' => 'Open',
                        'closed' => 'Closed',
                        'none' => 'None',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value']) {
                            if ($data['value'] === 'none') {
                                $query->whereDoesHave('purchaseDecision');
                            } else {
                                $query->whereHas('purchaseDecision', function ($q) use ($data) {
                                    $q->where('status', $data['value']);
                                });
                            }
                        }
                    }),
                Tables\Filters\SelectFilter::make('gold_grade')
                    ->label('Gold Grade')
                    ->options([
                        '16' => '16 ပဲ',
                        '15' => '15 ပဲ',
                        '14.2' => 'ဒင်္ဂါး (14.2)',
                        '14' => '14 ပဲ',
                        '13' => '13 ပဲ',
                        '12' => '12 ပဲ',
                        '10' => '10 ပဲ',
                        '8' => '8 ပဲ',
                        '4' => '4 ပဲ',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value']) {
                            $query->whereHas('items', function ($q) use ($data) {
                                $q->whereRaw("json_unquote(json_extract(dynamic_fields_json, '$.goldList')) = ?", [$data['value']]);
                            });
                        }
                    }),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Start Date')
                            ->default(now()),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('End Date')
                            ->default(now()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                \Filament\Actions\Action::make('view_history')
                    ->label('History')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->modalHeading(fn($record) => "Calculation History for Request {$record->purchase_number}")
                    ->modalWidth('5xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->form(function ($record) {
                        return [
                            Forms\Components\Placeholder::make('history_log')
                                ->hiddenLabel()
                                ->content(function () use ($record) {
                                    $histories = \App\Modules\Core\Calculation\Models\CalculationHistory::whereHasMorph(
                                        'calculatable',
                                        [\App\Modules\Purchase\Models\PurchaseItem::class],
                                        fn($query) => $query->where('purchase_request_id', $record->id)
                                    )->with('user', 'calculatable.productType')->latest()->get();

                                    if ($histories->isEmpty()) {
                                        return new \Illuminate\Support\HtmlString('<div class="text-center py-4 text-gray-500">No calculation history recorded yet.</div>');
                                    }

                                    $html = '<div class="overflow-x-auto"><table class="w-full text-left border-collapse text-sm">';
                                    $html .= '<thead><tr class="border-b border-gray-200 dark:border-gray-800 text-gray-400 font-semibold">';
                                    $html .= '<th class="py-2 pr-4">Date/Time</th>';
                                    $html .= '<th class="py-2 px-4">Operator</th>';
                                    $html .= '<th class="py-2 px-4">Product Name</th>';
                                    $html .= '<th class="py-2 px-4">Weight</th>';
                                    $html .= '<th class="py-2 px-4">Gold Grade</th>';
                                    $html .= '<th class="py-2 px-4">Qty</th>';
                                    $html .= '<th class="py-2 px-4">အလဲအထပ်</th>';
                                    $html .= '<th class="py-2 px-4">ရ/မရ</th>';
                                    $html .= '<th class="py-2 px-4">Deduction</th>';
                                    $html .= '<th class="py-2 px-4">Price</th>';
                                    $html .= '<th class="py-2 px-4">Remark</th>';
                                    $html .= '</tr></thead><tbody>';

                                    foreach ($histories as $history) {
                                        $inputs = $history->input_snapshot_json ?? [];
                                        $productName = $inputs['product_name'] ?? '-';
                                        $goldGrade = ($inputs['goldList'] ?? '-') . ' ပဲ';
                                        $weight = ($inputs['goldWeightGram'] ?? '0') . ' g';
                                        if (!empty($inputs['kyat']) || !empty($inputs['pae']) || !empty($inputs['yawe'])) {
                                            $weight = ($inputs['kyat'] ?? 0) . 'ကျပ် ' . ($inputs['pae'] ?? 0) . 'ပဲ ' . ($inputs['yawe'] ?? 0) . 'ရွေး (' . ($inputs['goldWeightGram'] ?? '0') . ' g)';
                                        }
                                        $date = $history->created_at->format('d M Y, h:i A');
                                        $operator = $history->user?->name ?? 'System';
                                        $price = number_format($history->total_amount) . ' MMK';
                                        $qty = $inputs['quantity'] ?? 1;
                                        $reChange = ($inputs['reChange'] ?? '0') === '1' ? 'အလဲအထပ် (Yes)' : (($inputs['reChange'] ?? '0') === '2' ? 'Percent ထည်ပြန်ဝယ်' : 'ဆိုင်ထည် (No)');
                                        $isGood = ($inputs['is_good'] ?? false) ? 'ရ' : 'မရ';
                                        $deduction = ($inputs['percent'] ?? 0) . '%';
                                        $remark = $inputs['remark'] ?? '-';

                                        $html .= '<tr class="border-b border-gray-100 dark:border-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800/50">';
                                        $html .= '<td class="py-3 pr-4 font-medium">' . e($date) . '</td>';
                                        $html .= '<td class="py-3 px-4">' . e($operator) . '</td>';
                                        $html .= '<td class="py-3 px-4">' . e($productName) . '</td>';
                                        $html .= '<td class="py-3 px-4">' . e($weight) . '</td>';
                                        $html .= '<td class="py-3 px-4">' . e($goldGrade) . '</td>';
                                        $html .= '<td class="py-3 px-4">' . e($qty) . '</td>';
                                        $html .= '<td class="py-3 px-4">' . e($reChange) . '</td>';
                                        $html .= '<td class="py-3 px-4">' . e($isGood) . '</td>';
                                        $html .= '<td class="py-3 px-4">' . e($deduction) . '</td>';
                                        $html .= '<td class="py-3 px-4 font-semibold text-success-600">' . e($price) . '</td>';
                                        $html .= '<td class="py-3 px-4 text-gray-500">' . e($remark) . '</td>';
                                        $html .= '</tr>';
                                    }

                                    $html .= '</tbody></table></div>';

                                    return new \Illuminate\Support\HtmlString($html);
                                })
                        ];
                    }),
                \Filament\Actions\EditAction::make(),
            ])
            ->defaultSort('id', 'desc')
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\BulkAction::make('export_items_excel')
                        ->label('Export Selected Items to Excel')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->action(function (\Illuminate\Support\Collection $records) {
                            $records->load(['items.purchaseRequest.branch', 'items.purchaseRequest.workflowState', 'items.productType']);
                            
                            return new \Symfony\Component\HttpFoundation\StreamedResponse(function () use ($records) {
                                $handle = fopen('php://output', 'w');
                                fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
                                
                                fputcsv($handle, [
                                    'Sr.',
                                    'Purchase No.',
                                    'Customer Name',
                                    'Phone No.',
                                    'Product Name',
                                    'Gold Grade',
                                    'Weight (Gram)',
                                    'Weight (K/P/Y)',
                                    'Qty',
                                    'ရ/မရ',
                                    'Price'
                                ]);
                                
                                $sr = 1;
                                $totalGrams = 0.0;
                                $totalKyat = 0;
                                $totalPae = 0;
                                $totalYawe = 0;
                                $totalQty = 0;
                                $totalPrice = 0;
                                
                                foreach ($records as $record) {
                                    foreach ($record->items as $item) {
                                        $inputs = $item->dynamic_fields_json ?? [];
                                        $productName = $inputs['product_name'] ?? '-';
                                        $goldGrade = ($inputs['goldList'] ?? '-') . ' ပဲ';
                                        $weightGram = floatval($inputs['goldWeightGram'] ?? 0);
                                        $k = intval($inputs['kyat'] ?? 0);
                                        $p = intval($inputs['pae'] ?? 0);
                                        $y = intval($inputs['yawe'] ?? 0);
                                        $weightKpy = "{$k}ကျပ် {$p}ပဲ {$y}ရွေး";
                                        $qty = intval($inputs['quantity'] ?? 1);
                                        $isGood = ($inputs['is_good'] ?? false) ? 'ရ' : 'မရ';
                                        $priceVal = floatval($item->calculated_price);
                                        $priceText = number_format($priceVal) . ' MMK';
                                        
                                        fputcsv($handle, [
                                            $sr++,
                                            $record->purchase_number,
                                            $record->customer_name,
                                            $record->customer_phone,
                                            $productName,
                                            $goldGrade,
                                            number_format($weightGram, 2) . ' g',
                                            $weightKpy,
                                            $qty,
                                            $isGood,
                                            $priceText
                                        ]);
                                        
                                        $totalGrams += $weightGram;
                                        $totalKyat += $k;
                                        $totalPae += $p;
                                        $totalYawe += $y;
                                        $totalQty += $qty;
                                        $totalPrice += $priceVal;
                                    }
                                }
                                
                                $extraPae = floor($totalYawe / 8);
                                $totalYawe = $totalYawe % 8;
                                $totalPae += $extraPae;
                                
                                $extraKyat = floor($totalPae / 16);
                                $totalPae = $totalPae % 16;
                                $totalKyat += $extraKyat;
                                
                                fputcsv($handle, [
                                    'Grand Total:',
                                    '',
                                    '',
                                    '',
                                    '',
                                    '',
                                    number_format($totalGrams, 2) . ' g',
                                    "{$totalKyat}ကျပ် {$totalPae}ပဲ {$totalYawe}ရွေး",
                                    $totalQty,
                                    '',
                                    number_format($totalPrice) . ' MMK'
                                ]);
                                
                                fclose($handle);
                            }, 200, [
                                'Content-Type' => 'text/csv; charset=UTF-8',
                                'Content-Disposition' => 'attachment; filename="purchase_items_export_' . now()->format('Y-m-d_H-i-s') . '.csv"',
                            ]);
                        }),
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Relation managers will be added here
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseRequests::route('/'),
            'create' => Pages\CreatePurchaseRequest::route('/create'),
            'edit' => Pages\EditPurchaseRequest::route('/{record}/edit'),
        ];
    }
}
