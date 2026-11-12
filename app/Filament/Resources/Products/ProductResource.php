<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Support\Fields;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'sku'];
    }

    public static function getNavigationBadge(): ?string
    {
        $low = Product::where('track_stock', true)->whereColumn('stock', '<=', 'low_stock_threshold')->where('is_active', true)->count();

        return $low ? (string) $low : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Products low / out of stock';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Group::make()->columnSpan(['lg' => 2])->schema([
                Section::make('Product information')->schema([
                    Fields::nameWithSlug('name', 'Product name'),
                    Fields::slug('product'),
                    Textarea::make('short_description')
                        ->rows(3)
                        ->maxLength(1000)
                        ->helperText('Shown next to the price. 1–3 short sentences that sell the product.'),
                    RichEditor::make('description')
                        ->label('Full description')
                        ->fileAttachmentsDisk('public')
                        ->fileAttachmentsDirectory('products/content')
                        ->columnSpanFull(),
                ]),

                Section::make('Images')->schema([
                    Fields::image('thumbnail', 'products', 'Main image')
                        ->required()
                        ->helperText('Square images (1000×1000) look best. Auto-converted to fast WebP.'),
                    FileUpload::make('gallery')
                        ->label('Gallery')
                        ->image()
                        ->multiple()
                        ->reorderable()
                        ->appendFiles()
                        ->panelLayout('grid')
                        ->disk('public')
                        ->directory('products')
                        ->maxFiles(12)
                        ->maxSize(5120),
                    TextInput::make('video_url')->label('YouTube video URL')->url()->placeholder('https://youtube.com/watch?v=…'),
                ]),

                Section::make('Pricing')->columns(2)->schema([
                    TextInput::make('price')->label('Regular price (MRP)')->numeric()->required()->minValue(0)->prefix(fn () => setting('general.currency_symbol'))->live(onBlur: true),
                    TextInput::make('sale_price')->label('Sale price')->numeric()->minValue(0)->prefix(fn () => setting('general.currency_symbol'))
                        ->lt('price')->validationMessages(['lt' => 'Sale price must be lower than the regular price.'])
                        ->live(onBlur: true)
                        ->helperText(fn (Get $get) => ($get('price') > 0 && $get('sale_price') > 0)
                            ? 'Discount: '.round((1 - $get('sale_price') / $get('price')) * 100).'% off'
                            : 'Leave empty if not on sale.'),
                    DateTimePicker::make('sale_ends_at')->label('Sale ends at')->native(false)->helperText('Optional. Shows a countdown timer and ends the sale automatically.'),
                    TextInput::make('cost_price')->label('Cost price')->numeric()->prefix(fn () => setting('general.currency_symbol'))->helperText('Private – used for profit reports.'),
                ]),

                Section::make('Inventory')->columns(3)->schema([
                    TextInput::make('sku')->label('SKU')->maxLength(100)->unique(ignoreRecord: true),
                    TextInput::make('stock')->numeric()->default(0)->integer()
                        ->disabled(fn (Get $get) => (bool) $get('has_variants'))
                        ->helperText(fn (Get $get) => $get('has_variants') ? 'Calculated from variants.' : null),
                    TextInput::make('low_stock_threshold')->numeric()->integer()->default(5)->helperText('Shows “Only X left” urgency below this.'),
                    Toggle::make('track_stock')->label('Track stock')->default(true)->inline(false),
                ]),

                Section::make('Variants')
                    ->description('Sizes, colours… each combination can have its own stock, price and image.')
                    ->schema([
                        Toggle::make('has_variants')->label('This product has options (size, colour…)')->live(),
                        Repeater::make('options')
                            ->label('Options')
                            ->visible(fn (Get $get) => $get('has_variants'))
                            ->schema([
                                TextInput::make('name')->label('Option name')->placeholder('Size')->required(),
                                TagsInput::make('values')->label('Values')->placeholder('Type and press Enter')->required()->splitKeys(['Tab', ',']),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->addActionLabel('Add another option')
                            ->reorderable(false),
                        Repeater::make('variants')
                            ->relationship()
                            ->visible(fn (Get $get) => $get('has_variants'))
                            ->label('Variants')
                            ->hintAction(
                                Action::make('generateVariants')
                                    ->label('Generate from options')
                                    ->icon('heroicon-o-sparkles')
                                    ->action(fn (Get $get, Set $set) => $set('variants', self::generateVariants((array) $get('options'), (array) $get('variants'), (string) $get('sku'))))
                            )
                            ->schema([
                                TextInput::make('name')->required()->columnSpan(2),
                                TextInput::make('sku')->label('SKU'),
                                TextInput::make('stock')->numeric()->integer()->default(0)->required(),
                                TextInput::make('price')->numeric()->placeholder('Same as product')->helperText('Optional'),
                                TextInput::make('sale_price')->numeric()->placeholder('—'),
                                Fields::image('image', 'products/variants', 'Image')->columnSpan(2),
                                KeyValue::make('options')->keyLabel('Option')->valueLabel('Value')->columnSpan(2)->addable(false)->deletable(false)->editableKeys(false),
                                Toggle::make('is_active')->label('Available')->default(true),
                            ])
                            ->columns(4)
                            ->orderColumn('sort_order')
                            ->collapsible()
                            ->itemLabel(fn (array $state) => ($state['name'] ?? 'Variant').(isset($state['stock']) ? ' · stock '.$state['stock'] : ''))
                            ->addActionLabel('Add variant manually'),
                    ]),

                Section::make('Specifications')
                    ->collapsible()
                    ->schema([
                        Repeater::make('specifications')
                            ->hiddenLabel()
                            ->schema([
                                TextInput::make('label')->required()->placeholder('Material'),
                                TextInput::make('value')->placeholder('100% Cotton'),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->addActionLabel('Add specification'),
                    ]),

                Fields::seo('name', 'short_description'),
            ]),

            Group::make()->columnSpan(['lg' => 1])->schema([
                Section::make('Status')->schema([
                    Toggle::make('is_active')->label('Visible in store')->default(true),
                    Toggle::make('is_featured')->label('Featured product'),
                    TextInput::make('badge')->maxLength(40)->placeholder('Hot, Best Seller, Limited…')->helperText('Small label on the product image.'),
                ]),
                Section::make('Organization')->schema([
                    Select::make('category_id')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->createOptionForm([
                            TextInput::make('name')->required(),
                            Select::make('parent_id')->relationship('parent', 'name')->label('Parent'),
                        ]),
                    Select::make('brand_id')
                        ->relationship('brand', 'name')
                        ->searchable()
                        ->preload()
                        ->createOptionForm([TextInput::make('name')->required()]),
                    TextInput::make('meta_keywords')->label('Search keywords')->helperText('Extra words customers may search for (comma separated).'),
                    TextInput::make('weight')->numeric()->suffix('kg'),
                ]),
                Section::make('Performance')
                    ->visibleOn('edit')
                    ->columns(2)
                    ->schema([
                        TextInput::make('views')->disabled()->numeric(),
                        TextInput::make('sold_count')->label('Sold')->disabled()->numeric(),
                        TextInput::make('rating_avg')->label('Rating')->disabled(),
                        TextInput::make('rating_count')->label('Reviews')->disabled(),
                    ]),
            ]),
        ]);
    }

    /** Build every option combination, keeping existing variants that still match. */
    public static function generateVariants(array $options, array $existing, string $sku): array
    {
        $options = array_values(array_filter($options, fn ($o) => filled($o['name'] ?? null) && ! empty($o['values'])));
        if (! $options) {
            return $existing;
        }

        $combos = [[]];
        foreach ($options as $opt) {
            $next = [];
            foreach ($combos as $combo) {
                foreach ((array) $opt['values'] as $value) {
                    $next[] = $combo + [$opt['name'] => $value];
                }
            }
            $combos = $next;
        }

        $bySignature = [];
        foreach ($existing as $key => $item) {
            $bySignature[json_encode((array) ($item['options'] ?? []))] = [$key, $item];
        }

        $result = [];
        foreach ($combos as $combo) {
            $sig = json_encode($combo);
            if (isset($bySignature[$sig])) {
                [$key, $item] = $bySignature[$sig];
                $result[$key] = $item;

                continue;
            }
            $result[(string) Str::uuid()] = [
                'name' => implode(' / ', $combo),
                'options' => $combo,
                'sku' => $sku ? $sku.'-'.Str::upper(Str::slug(implode('-', $combo))) : null,
                'stock' => 0,
                'price' => null,
                'sale_price' => null,
                'image' => null,
                'is_active' => true,
            ];
        }

        return $result;
    }

    public static function table(Table $table): Table
    {
        $money = fn ($state) => $state !== null ? money($state) : '—';

        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                ImageColumn::make('thumbnail')->label('')->disk('public')->square()->imageSize(48),
                TextColumn::make('name')
                    ->searchable(['name', 'sku'])
                    ->sortable()
                    ->limit(48)
                    ->description(fn (Product $r) => trim(($r->sku ? 'SKU '.$r->sku : '').($r->has_variants ? ' · variants' : ''), ' ·'))
                    ->weight('medium'),
                TextColumn::make('category.name')->label('Category')->sortable()->toggleable(),
                TextColumn::make('price')
                    ->label('Price')
                    ->sortable()
                    ->formatStateUsing(fn (Product $r) => money($r->final_price))
                    ->description(fn (Product $r) => $r->isOnSale() ? 'was '.money($r->price).' (−'.$r->discount_percent.'%)' : null),
                TextColumn::make('stock')
                    ->sortable()
                    ->badge()
                    ->color(fn (Product $r) => ! $r->track_stock ? 'gray' : ($r->stock <= 0 ? 'danger' : ($r->stock <= $r->low_stock_threshold ? 'warning' : 'success')))
                    ->formatStateUsing(fn (Product $r) => $r->track_stock ? $r->stock : '∞'),
                TextColumn::make('sold_count')->label('Sold')->sortable()->toggleable(),
                TextColumn::make('rating_avg')->label('Rating')->formatStateUsing(fn (Product $r) => $r->rating_count ? number_format($r->rating_avg, 1).' ★ ('.$r->rating_count.')' : '—')->sortable()->toggleable(isToggledHiddenByDefault: true),
                ToggleColumn::make('is_featured')->label('Featured')->toggleable(),
                ToggleColumn::make('is_active')->label('Active'),
                TextColumn::make('updated_at')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')->relationship('category', 'name')->searchable()->preload(),
                SelectFilter::make('brand')->relationship('brand', 'name')->searchable()->preload(),
                SelectFilter::make('stock_status')
                    ->options(['in' => 'In stock', 'low' => 'Low stock', 'out' => 'Out of stock'])
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'in' => $query->where(fn ($q) => $q->where('track_stock', false)->orWhereColumn('stock', '>', 'low_stock_threshold')),
                        'low' => $query->where('track_stock', true)->where('stock', '>', 0)->whereColumn('stock', '<=', 'low_stock_threshold'),
                        'out' => $query->where('track_stock', true)->where('stock', '<=', 0),
                        default => $query,
                    }),
                TernaryFilter::make('on_sale')->label('On sale')
                    ->queries(true: fn (Builder $query) => $query->onSale(), false: fn (Builder $query) => $query->whereNull('sale_price')),
                TernaryFilter::make('is_active')->label('Visible'),
                TernaryFilter::make('is_featured')->label('Featured'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('view_store')->label('')->tooltip('View in store')->icon('heroicon-o-arrow-top-right-on-square')->color('gray')
                    ->url(fn (Product $r) => $r->url, shouldOpenInNewTab: true),
                ReplicateAction::make()->label('')->tooltip('Duplicate')
                    ->excludeAttributes(['views', 'sold_count', 'rating_avg', 'rating_count'])
                    ->beforeReplicaSaved(function (Product $replica) {
                        $replica->name .= ' (copy)';
                        $replica->slug = null;
                        $replica->sku = $replica->sku ? $replica->sku.'-COPY-'.Str::upper(Str::random(3)) : null;
                        $replica->is_active = false;
                    })
                    ->successRedirectUrl(fn (Product $replica) => self::getUrl('edit', ['record' => $replica])),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')->label('Show in store')->icon('heroicon-o-eye')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true]))->deselectRecordsAfterCompletion(),
                    BulkAction::make('deactivate')->label('Hide from store')->icon('heroicon-o-eye-slash')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false]))->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
