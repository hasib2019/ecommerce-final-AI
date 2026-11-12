<?php

namespace App\Filament\Resources\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Services\CheckoutService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static string|UnitEnum|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'order_number';

    public static function getGloballySearchableAttributes(): array
    {
        return ['order_number', 'customer_name', 'customer_phone'];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return ['Customer' => $record->customer_name.' · '.$record->customer_phone, 'Total' => money($record->total)];
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = Order::where('status', OrderStatus::Pending)->count();

        return $pending ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'New orders waiting for confirmation';
    }

    // ------------------------------------------------------------------ form (create / edit)

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Group::make()->columnSpan(['lg' => 2])->schema([
                Section::make('Customer & delivery')->columns(2)->schema([
                    TextInput::make('customer_name')->label('Name')->required()->maxLength(120),
                    TextInput::make('customer_phone')->label('Phone')->tel()->required()->maxLength(20),
                    TextInput::make('customer_email')->label('Email')->email(),
                    Select::make('city')->label('District')->options(array_combine(config('shop.districts'), config('shop.districts')))->searchable()->default('Dhaka'),
                    Textarea::make('shipping_address')->label('Address')->required()->rows(2)->columnSpanFull(),
                    Textarea::make('note')->label('Customer note')->rows(2)->columnSpanFull(),
                ]),

                Section::make('Items')
                    ->visibleOn('create')
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->hiddenLabel()
                            ->schema([
                                Select::make('product_id')
                                    ->label('Product')
                                    ->options(fn () => Product::active()->orderBy('name')->limit(1000)->pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $product = Product::find($state);
                                        $set('product_variant_id', null);
                                        $set('price', $product?->final_price);
                                        $set('product_name', $product?->name);
                                        $set('sku', $product?->sku);
                                        $set('image', $product?->thumbnail);
                                        self::recalculate($get, $set, '../../');
                                    })
                                    ->columnSpan(3),
                                Select::make('product_variant_id')
                                    ->label('Variant')
                                    ->options(fn (Get $get) => ProductVariant::where('product_id', $get('product_id'))->where('is_active', true)->pluck('name', 'id'))
                                    ->visible(fn (Get $get) => ProductVariant::where('product_id', $get('product_id'))->exists())
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $product = Product::find($get('product_id'));
                                        $variant = ProductVariant::find($state);
                                        if ($product) {
                                            $set('price', $product->pricingFor($variant)[0]);
                                        }
                                        $set('variant_name', $variant?->name);
                                        self::recalculate($get, $set, '../../');
                                    })
                                    ->columnSpan(2),
                                TextInput::make('quantity')->numeric()->integer()->minValue(1)->default(1)->required()->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculate($get, $set, '../../')),
                                TextInput::make('price')->numeric()->required()->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculate($get, $set, '../../')),
                                \Filament\Forms\Components\Hidden::make('product_name'),
                                \Filament\Forms\Components\Hidden::make('variant_name'),
                                \Filament\Forms\Components\Hidden::make('sku'),
                                \Filament\Forms\Components\Hidden::make('image'),
                            ])
                            ->columns(7)
                            ->defaultItems(1)
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculate($get, $set, ''))
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data) {
                                $data['total'] = round((float) $data['price'] * (int) $data['quantity'], 2);
                                $data['product_name'] ??= Product::find($data['product_id'])?->name ?? 'Product';

                                return $data;
                            })
                            ->addActionLabel('Add product'),
                    ]),
            ]),

            Group::make()->schema([
                Section::make('Status')->schema([
                    Select::make('status')->options(OrderStatus::class)->default(OrderStatus::Pending)->required()->native(false),
                    Select::make('payment_status')->options(PaymentStatus::class)->default(PaymentStatus::Unpaid)->required()->native(false),
                    Select::make('payment_method')->options(fn () => collect(CheckoutService::paymentMethods())->map(fn ($m) => $m['label'])->all() + ['cod' => 'Cash on Delivery'])->default('cod')->required(),
                    TextInput::make('transaction_id')->label('Transaction ID'),
                    Textarea::make('admin_note')->label('Internal note')->rows(3)->helperText('Only visible to staff.'),
                ]),
                Section::make('Totals')->schema([
                    Select::make('shipping_method')
                        ->options(fn () => ShippingMethod::orderBy('sort_order')->pluck('name', 'name'))
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                            $set('shipping_cost', (float) (ShippingMethod::where('name', $state)->value('cost') ?? 0));
                            self::recalculate($get, $set, '');
                        }),
                    TextInput::make('subtotal')->numeric()->required()->default(0)->readOnlyOn('create')->prefix(fn () => setting('general.currency_symbol')),
                    TextInput::make('shipping_cost')->label('Delivery charge')->numeric()->default(0)->live(onBlur: true)->prefix(fn () => setting('general.currency_symbol'))
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculate($get, $set, '')),
                    TextInput::make('discount')->numeric()->default(0)->live(onBlur: true)->prefix(fn () => setting('general.currency_symbol'))
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculate($get, $set, '')),
                    TextInput::make('total')->numeric()->required()->default(0)->prefix(fn () => setting('general.currency_symbol'))->extraInputAttributes(['class' => 'font-bold']),
                ]),
            ]),
        ]);
    }

    /** Re-compute subtotal & total from the items repeater. $prefix points to the root form state. */
    public static function recalculate(Get $get, Set $set, string $prefix): void
    {
        $items = (array) $get($prefix.'items');
        if (! $items && $prefix !== '') {
            return;
        }

        $subtotal = collect($items)->sum(fn ($i) => (float) ($i['price'] ?? 0) * (int) ($i['quantity'] ?? 0));
        if ($items) {
            $set($prefix.'subtotal', round($subtotal, 2));
        } else {
            $subtotal = (float) $get($prefix.'subtotal');
        }

        $total = max(0, $subtotal - (float) $get($prefix.'discount')) + (float) $get($prefix.'shipping_cost');
        $set($prefix.'total', round($total, 2));
    }

    // ------------------------------------------------------------------ view

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Group::make()->columnSpan(['lg' => 2])->schema([
                Section::make('Items')->schema([
                    RepeatableEntry::make('items')
                        ->hiddenLabel()
                        ->table([
                            \Filament\Infolists\Components\RepeatableEntry\TableColumn::make('Image')->width('70px'),
                            \Filament\Infolists\Components\RepeatableEntry\TableColumn::make('Product'),
                            \Filament\Infolists\Components\RepeatableEntry\TableColumn::make('Price'),
                            \Filament\Infolists\Components\RepeatableEntry\TableColumn::make('Qty'),
                            \Filament\Infolists\Components\RepeatableEntry\TableColumn::make('Total'),
                        ])
                        ->schema([
                            ImageEntry::make('image')->disk('public')->imageSize(48),
                            TextEntry::make('product_name')->helperText(fn ($record) => trim(($record->variant_name ?? '').($record->sku ? ' · '.$record->sku : ''), ' ·')),
                            TextEntry::make('price')->formatStateUsing(fn ($state) => money($state)),
                            TextEntry::make('quantity'),
                            TextEntry::make('total')->formatStateUsing(fn ($state) => money($state))->weight('bold'),
                        ]),
                    Grid::make(4)->schema([
                        TextEntry::make('subtotal')->formatStateUsing(fn ($state) => money($state)),
                        TextEntry::make('discount')->formatStateUsing(fn ($state, Order $r) => $state > 0 ? '−'.money($state).($r->coupon_code ? " ({$r->coupon_code})" : '') : '—'),
                        TextEntry::make('shipping_cost')->label('Delivery')->formatStateUsing(fn ($state) => money($state)),
                        TextEntry::make('total')->formatStateUsing(fn ($state) => money($state))->weight('bold')->size('lg')->color('primary'),
                    ]),
                ]),
                Section::make('Timeline')->collapsible()->schema([
                    RepeatableEntry::make('histories')
                        ->hiddenLabel()
                        ->schema([
                            TextEntry::make('status')->badge()->hiddenLabel()
                                ->suffix(fn ($record) => $record->note ? ' – '.$record->note : '')
                                ->helperText(fn ($record) => $record->created_at->format('d M Y, h:i A').' ('.$record->created_at->diffForHumans().')'),
                        ]),
                ]),
            ]),
            Group::make()->schema([
                Section::make('Order')->schema([
                    TextEntry::make('order_number')->copyable()->weight('bold'),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('created_at')->label('Placed')->dateTime('d M Y, h:i A'),
                ]),
                Section::make('Customer')->schema([
                    TextEntry::make('customer_name')->label('Name')->weight('medium'),
                    TextEntry::make('customer_phone')->label('Phone')->copyable()->url(fn (Order $r) => 'tel:'.$r->customer_phone),
                    TextEntry::make('customer_email')->label('Email')->placeholder('—'),
                    TextEntry::make('shipping_address')->label('Address')->formatStateUsing(fn ($state, Order $r) => $state.($r->city ? ', '.$r->city : '')),
                    TextEntry::make('note')->label('Customer note')->placeholder('—'),
                    TextEntry::make('customer_orders')->label('Previous orders')
                        ->state(fn (Order $r) => Order::where('customer_phone', $r->customer_phone)->whereKeyNot($r->id)->count())
                        ->badge()->color(fn ($state) => $state > 0 ? 'success' : 'gray'),
                ]),
                Section::make('Payment & shipping')->schema([
                    TextEntry::make('payment_method')->formatStateUsing(fn (Order $r) => $r->paymentLabel()),
                    TextEntry::make('payment_status')->badge(),
                    TextEntry::make('transaction_id')->label('Transaction ID')->copyable()->placeholder('—'),
                    TextEntry::make('shipping_method')->placeholder('—'),
                    TextEntry::make('admin_note')->label('Internal note')->placeholder('—'),
                ]),
            ]),
        ]);
    }

    // ------------------------------------------------------------------ table

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('items'))
            ->columns([
                TextColumn::make('order_number')->label('Order')->searchable()->sortable()->weight('bold')->copyable()
                    ->description(fn (Order $r) => $r->created_at->format('d M, h:i A')),
                TextColumn::make('customer_name')->label('Customer')->searchable(['customer_name', 'customer_phone'])
                    ->description(fn (Order $r) => $r->customer_phone),
                TextColumn::make('city')->toggleable()->sortable(),
                TextColumn::make('items_count')->label('Items')->badge()->color('gray'),
                TextColumn::make('total')->formatStateUsing(fn ($state) => money($state))->sortable()->weight('medium'),
                TextColumn::make('payment_method')->label('Payment')->formatStateUsing(fn (Order $r) => $r->paymentLabel())
                    ->description(fn (Order $r) => $r->payment_status->getLabel())->toggleable(),
                SelectColumn::make('status')->options(OrderStatus::class)->selectablePlaceholder(false)->sortable(),
                TextColumn::make('created_at')->label('Date')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(OrderStatus::class)->multiple(),
                SelectFilter::make('payment_status')->options(PaymentStatus::class),
                SelectFilter::make('payment_method')->options(['cod' => 'Cash on Delivery', 'bkash' => 'bKash', 'nagad' => 'Nagad', 'bank' => 'Bank']),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->native(false),
                        DatePicker::make('until')->native(false),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                        ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d)))
                    ->indicateUsing(fn (array $data) => array_filter([
                        ($data['from'] ?? null) ? 'From '.$data['from'] : null,
                        ($data['until'] ?? null) ? 'Until '.$data['until'] : null,
                    ])),
            ])
            ->recordActions([
                ViewAction::make()->label(''),
                ActionGroup::make([
                    Action::make('confirm')->label('Confirm order')->icon('heroicon-o-check-circle')->color('success')
                        ->visible(fn (Order $r) => $r->status === OrderStatus::Pending)
                        ->action(fn (Order $r) => $r->update(['status' => OrderStatus::Confirmed])),
                    Action::make('ship')->label('Mark as shipped')->icon('heroicon-o-truck')->color('info')
                        ->visible(fn (Order $r) => in_array($r->status, [OrderStatus::Confirmed, OrderStatus::Processing], true))
                        ->action(fn (Order $r) => $r->update(['status' => OrderStatus::Shipped])),
                    Action::make('deliver')->label('Mark as delivered')->icon('heroicon-o-gift')->color('success')
                        ->visible(fn (Order $r) => $r->status === OrderStatus::Shipped)
                        ->action(fn (Order $r) => $r->update(['status' => OrderStatus::Delivered])),
                    Action::make('invoice')->label('Print invoice')->icon('heroicon-o-printer')
                        ->url(fn (Order $r) => route('admin.invoice', $r), shouldOpenInNewTab: true),
                    Action::make('call')->label('Call customer')->icon('heroicon-o-phone')
                        ->url(fn (Order $r) => 'tel:'.$r->customer_phone),
                    Action::make('whatsapp')->label('WhatsApp customer')->icon('heroicon-o-chat-bubble-left-right')
                        ->url(fn (Order $r) => 'https://wa.me/88'.ltrim(preg_replace('/\D/', '', $r->customer_phone), '88').'?text='.rawurlencode("Hello {$r->customer_name}, regarding your order #{$r->order_number} from ".setting('general.site_name').'.'), true),
                    EditAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('updateStatus')
                        ->label('Change status')
                        ->icon('heroicon-o-arrow-path')
                        ->schema([Select::make('status')->options(OrderStatus::class)->required()])
                        ->action(function (Collection $records, array $data) {
                            $records->each(fn (Order $o) => $o->update(['status' => $data['status']]));
                            Notification::make()->title($records->count().' orders updated')->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('export')
                        ->label('Export CSV')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(fn (Collection $records) => response()->streamDownload(function () use ($records) {
                            $out = fopen('php://output', 'w');
                            fputcsv($out, ['Order', 'Date', 'Customer', 'Phone', 'Address', 'City', 'Items', 'Subtotal', 'Delivery', 'Discount', 'Total', 'Payment', 'Payment status', 'Status', 'Note']);
                            foreach ($records->load('items') as $o) {
                                fputcsv($out, [
                                    $o->order_number, $o->created_at->format('Y-m-d H:i'), $o->customer_name, $o->customer_phone, $o->shipping_address, $o->city,
                                    $o->items->map(fn ($i) => $i->product_name.($i->variant_name ? " ({$i->variant_name})" : '').' x'.$i->quantity)->implode('; '),
                                    $o->subtotal, $o->shipping_cost, $o->discount, $o->total, $o->paymentLabel(), $o->payment_status->getLabel(), $o->status->getLabel(), $o->note,
                                ]);
                            }
                            fclose($out);
                        }, 'orders-'.now()->format('Y-m-d-His').'.csv')),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'view' => ViewOrder::route('/{record}'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }
}
