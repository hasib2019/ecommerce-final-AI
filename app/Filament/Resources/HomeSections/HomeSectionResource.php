<?php

namespace App\Filament\Resources\HomeSections;

use App\Filament\Resources\HomeSections\Pages\CreateHomeSection;
use App\Filament\Resources\HomeSections\Pages\EditHomeSection;
use App\Filament\Resources\HomeSections\Pages\ListHomeSections;
use App\Filament\Support\Fields;
use App\Models\Brand;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Product;
use App\Support\Icon;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class HomeSectionResource extends Resource
{
    protected static ?string $model = HomeSection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static string|UnitEnum|null $navigationGroup = 'Storefront';

    protected static ?string $navigationLabel = 'Home page builder';

    protected static ?string $modelLabel = 'home page section';

    protected static ?int $navigationSort = 1;

    private const SOURCES = [
        'latest' => 'Newest products', 'featured' => 'Featured products', 'best_selling' => 'Best sellers',
        'on_sale' => 'On sale', 'top_rated' => 'Top rated', 'category' => 'From a category', 'manual' => 'Hand-picked products',
    ];

    public static function form(Schema $schema): Schema
    {
        $is = fn (string ...$types) => fn (Get $get) => in_array($get('type'), $types, true);

        return $schema->columns(3)->components([
            Group::make()->columnSpan(['lg' => 2])->schema([
                Section::make('Section')->columns(2)->schema([
                    Select::make('type')->label('Section type')->options(HomeSection::TYPES)->required()->live()->native(false)->columnSpanFull(),
                    TextInput::make('title')->label('Heading')->maxLength(255),
                    TextInput::make('subtitle')->label('Sub-heading')->maxLength(255),
                ]),

                // ---------------- Hero slider
                Section::make('Slides')->visible($is('hero_slider'))->schema([
                    Repeater::make('data.slides')->hiddenLabel()->schema([
                        Grid::make(2)->schema([
                            Fields::image('image', 'banners', 'Desktop image')->required()->helperText('1600×700 recommended.'),
                            Fields::image('mobile_image', 'banners', 'Mobile image (optional)')->helperText('900×1000 – shown on phones.'),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('heading'),
                            TextInput::make('badge')->placeholder('Limited time'),
                            Textarea::make('subheading')->rows(2)->columnSpanFull(),
                            TextInput::make('button_text')->placeholder('Shop now'),
                            TextInput::make('button_link')->placeholder('/category/electronics'),
                            Select::make('align')->options(['left' => 'Left', 'center' => 'Center', 'right' => 'Right'])->default('left'),
                            Select::make('text_color')->options(['light' => 'Light text', 'dark' => 'Dark text'])->default('light'),
                            Toggle::make('overlay')->label('Dark overlay behind text'),
                        ]),
                    ])->collapsible()->cloneable()->itemLabel(fn (array $state) => $state['heading'] ?? 'Slide')->addActionLabel('Add slide')->defaultItems(1),
                    Repeater::make('data.side_banners')->label('Side banners (desktop, max 2)')->maxItems(2)->schema([
                        Fields::image('image', 'banners', 'Image')->required(),
                        Grid::make(2)->schema([
                            TextInput::make('heading'), TextInput::make('subheading'),
                            TextInput::make('button_text'), TextInput::make('link'),
                            Select::make('text_color')->options(['light' => 'Light text', 'dark' => 'Dark text'])->default('light'),
                        ]),
                    ])->collapsible()->collapsed()->defaultItems(0),
                    Grid::make(4)->schema([
                        Toggle::make('data.autoplay')->label('Autoplay')->default(true)->inline(false),
                        TextInput::make('data.interval')->label('Seconds per slide')->numeric()->default(5),
                        Select::make('data.height')->options(['sm' => 'Short', 'md' => 'Medium', 'lg' => 'Tall'])->default('md'),
                        Toggle::make('data.full_width')->label('Edge to edge')->inline(false),
                    ]),
                ]),

                // ---------------- Features
                Section::make('Items')->visible($is('features'))->schema([
                    Repeater::make('data.items')->hiddenLabel()->schema([
                        Select::make('icon')->options(Icon::choices())->default('truck')->required(),
                        TextInput::make('title')->required(),
                        TextInput::make('text'),
                    ])->columns(3)->maxItems(6)->defaultItems(4)->addActionLabel('Add item'),
                ]),

                // ---------------- Categories
                Section::make('Categories')->visible($is('category_grid'))->columns(2)->schema([
                    Select::make('data.style')->options(['circle' => 'Circles (scrollable on mobile)', 'card' => 'Large image cards', 'tile' => 'Compact tiles'])->default('circle')->required(),
                    TextInput::make('data.limit')->numeric()->default(12)->label('Max categories'),
                    Select::make('data.category_ids')->label('Categories to show')->multiple()->searchable()
                        ->options(fn () => Category::orderBy('name')->pluck('name', 'id'))
                        ->helperText('Leave empty to show featured & top-level categories automatically.')->columnSpanFull(),
                    TextInput::make('data.columns')->label('Columns (card style)')->numeric()->default(6),
                    TextInput::make('data.view_all_url')->label('“View all” link')->placeholder('/shop'),
                ]),

                // ---------------- Products / flash sale
                Section::make('Products')->visible($is('products', 'flash_sale'))->columns(2)->schema([
                    DateTimePicker::make('data.ends_at')->label('Sale ends at')->native(false)->visible($is('flash_sale'))->required(fn (Get $get) => $get('type') === 'flash_sale')
                        ->helperText('The section hides itself automatically when the timer ends.')->columnSpanFull(),
                    Select::make('data.source')->label('Which products?')->options(self::SOURCES)->default('latest')->required()->live(),
                    TextInput::make('data.limit')->label('How many')->numeric()->default(10)->minValue(1)->maxValue(48),
                    Select::make('data.category_id')->label('Category')->options(fn () => Category::orderBy('name')->pluck('name', 'id'))->searchable()
                        ->visible(fn (Get $get) => $get('data.source') === 'category')->required(fn (Get $get) => $get('data.source') === 'category'),
                    Select::make('data.product_ids')->label('Products')->multiple()->searchable()
                        ->getSearchResultsUsing(fn (string $search) => Product::where('name', 'like', "%{$search}%")->limit(30)->pluck('name', 'id'))
                        ->getOptionLabelsUsing(fn (array $values) => Product::whereIn('id', $values)->pluck('name', 'id'))
                        ->visible(fn (Get $get) => $get('data.source') === 'manual')->columnSpanFull(),
                    ToggleButtons::make('data.layout')->label('Layout')->options(['slider' => 'Slider', 'grid' => 'Grid'])->default('slider')->inline()->visible($is('products')),
                    ToggleButtons::make('data.columns')->label('Products per row (desktop)')->options([4 => '4', 5 => '5', 6 => '6'])->default(5)->inline(),
                    TextInput::make('data.view_all_url')->label('“View all” link')->placeholder('/shop?sort=popular'),
                    TextInput::make('data.view_all_text')->label('“View all” text')->placeholder('View all')->visible($is('products')),
                ]),

                // ---------------- Banners
                Section::make('Banners')->visible($is('banners'))->schema([
                    ToggleButtons::make('data.columns')->label('Banners per row')->options([1 => '1', 2 => '2', 3 => '3', 4 => '4'])->default(2)->inline(),
                    Repeater::make('data.banners')->hiddenLabel()->schema([
                        Grid::make(2)->schema([
                            Fields::image('image', 'banners', 'Image')->required(),
                            Fields::image('mobile_image', 'banners', 'Mobile image (optional)'),
                            TextInput::make('link')->placeholder('/category/…'),
                            TextInput::make('alt')->label('Alt text (SEO)'),
                            TextInput::make('heading')->helperText('Optional text on top of the image'),
                            TextInput::make('subheading'),
                            TextInput::make('button_text'),
                            Select::make('text_color')->options(['light' => 'Light text', 'dark' => 'Dark text'])->default('light'),
                        ]),
                    ])->collapsible()->itemLabel(fn (array $state) => $state['heading'] ?? $state['alt'] ?? 'Banner')->addActionLabel('Add banner'),
                ]),

                // ---------------- Promo banner
                Section::make('Promo banner')->visible($is('promo_banner'))->columns(2)->schema([
                    Fields::image('data.image', 'banners', 'Background image')->columnSpanFull(),
                    TextInput::make('data.heading')->columnSpanFull(),
                    Textarea::make('data.text')->rows(2)->columnSpanFull(),
                    TextInput::make('data.button_text'),
                    TextInput::make('data.button_link'),
                    DateTimePicker::make('data.ends_at')->label('Countdown to (optional)')->native(false),
                    Select::make('data.align')->options(['left' => 'Left', 'center' => 'Center', 'right' => 'Right'])->default('center'),
                ]),

                // ---------------- Brands
                Section::make('Brands')->visible($is('brands'))->columns(2)->schema([
                    Select::make('data.brand_ids')->label('Brands')->multiple()->searchable()->options(fn () => Brand::orderBy('name')->pluck('name', 'id'))
                        ->helperText('Leave empty to show all brands that have a logo.')->columnSpanFull(),
                    TextInput::make('data.limit')->numeric()->default(12),
                    Toggle::make('data.grayscale')->label('Grayscale logos (colour on hover)')->default(true)->inline(false),
                ]),

                // ---------------- Testimonials
                Section::make('Testimonials')->visible($is('testimonials'))->schema([
                    Repeater::make('data.items')->hiddenLabel()->schema([
                        TextInput::make('name')->required(),
                        TextInput::make('location'),
                        Select::make('rating')->options([5 => '★★★★★', 4 => '★★★★', 3 => '★★★'])->default(5),
                        Fields::image('avatar', 'testimonials', 'Photo'),
                        Textarea::make('text')->required()->rows(3)->columnSpanFull(),
                    ])->columns(2)->collapsible()->itemLabel(fn (array $state) => $state['name'] ?? 'Testimonial')->addActionLabel('Add testimonial'),
                ]),

                // ---------------- Blog
                Section::make('Blog posts')->visible($is('blog_posts'))->schema([
                    TextInput::make('data.limit')->label('Number of posts')->numeric()->default(3),
                ]),

                // ---------------- Newsletter
                Section::make('Newsletter')->visible($is('newsletter'))->columns(2)->schema([
                    TextInput::make('data.button_text')->default('Subscribe'),
                    Fields::image('data.image', 'banners', 'Background image (optional)'),
                ]),

                // ---------------- Rich text
                Section::make('Content')->visible($is('rich_text'))->schema([
                    RichEditor::make('data.content')->hiddenLabel()->fileAttachmentsDisk('public')->fileAttachmentsDirectory('content'),
                    Grid::make(2)->schema([
                        Select::make('data.width')->options(['wide' => 'Full width', 'narrow' => 'Narrow (reading width)'])->default('wide'),
                        Toggle::make('data.boxed')->label('White box background')->default(true)->inline(false),
                    ]),
                ]),
            ]),

            Group::make()->schema([
                Section::make('Display')->schema([
                    Toggle::make('is_active')->label('Visible')->default(true),
                    ToggleButtons::make('visibility')->label('Show on')
                        ->options(['all' => 'All devices', 'desktop' => 'Desktop only', 'mobile' => 'Mobile only'])
                        ->default('all')->inline()->grouped(),
                    ColorPicker::make('background')->label('Background colour')->helperText('Optional full-width band behind the section.'),
                ]),
                Section::make('Tips')->schema([
                    \Filament\Schemas\Components\Text::make('Drag sections in the list to reorder the home page. Changes are live instantly – the page cache refreshes automatically.'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->paginated(false)
            ->description('Drag ⇅ to reorder. Toggle to show / hide. Your home page is built from these sections, top to bottom.')
            ->columns([
                TextColumn::make('type')->badge()->formatStateUsing(fn ($state) => HomeSection::TYPES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'hero_slider', 'promo_banner', 'banners' => 'warning',
                        'products', 'flash_sale' => 'success',
                        'category_grid', 'brands' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('title')->placeholder('—')->weight('medium')->description(fn (HomeSection $r) => self::summary($r)),
                TextColumn::make('visibility')->badge()->color('gray')->formatStateUsing(fn ($state) => ['all' => 'All devices', 'desktop' => 'Desktop', 'mobile' => 'Mobile'][$state] ?? $state),
                ToggleColumn::make('is_active')->label('Visible'),
            ])
            ->headerActions([
                Action::make('preview')->label('Preview home page')->icon('heroicon-o-eye')->color('gray')->url(url('/'), shouldOpenInNewTab: true),
            ])
            ->recordActions([
                EditAction::make(),
                ReplicateAction::make()->label('')->tooltip('Duplicate')->beforeReplicaSaved(fn (HomeSection $r) => $r->sort_order = HomeSection::max('sort_order') + 1),
                DeleteAction::make()->label(''),
            ]);
    }

    private static function summary(HomeSection $r): string
    {
        return match ($r->type) {
            'hero_slider' => count((array) $r->get('slides', [])).' slides',
            'products', 'flash_sale' => ((int) $r->get('limit', 10)).' × '.(self::SOURCES[$r->get('source', 'latest')] ?? '').($r->get('ends_at') ? ' · ends '.\Illuminate\Support\Carbon::parse($r->get('ends_at'))->diffForHumans() : ''),
            'banners' => count((array) $r->get('banners', [])).' banners',
            'features', 'testimonials' => count((array) $r->get('items', [])).' items',
            'category_grid' => ucfirst($r->get('style', 'circle')).' style',
            default => HomeSection::TYPES[$r->type] ?? '',
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHomeSections::route('/'),
            'create' => CreateHomeSection::route('/create'),
            'edit' => EditHomeSection::route('/{record}/edit'),
        ];
    }
}
