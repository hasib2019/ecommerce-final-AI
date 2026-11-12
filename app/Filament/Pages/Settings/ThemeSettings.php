<?php

namespace App\Filament\Pages\Settings;

use App\Filament\Support\Fields;
use App\Filament\Support\LinkPicker;
use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;

class ThemeSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    protected static ?string $navigationLabel = 'Theme, header & footer';

    protected static ?string $title = 'Theme, header & footer';

    protected static string|\UnitEnum|null $navigationGroup = 'Storefront';

    protected static ?int $navigationSort = 2;

    protected function groups(): array
    {
        return ['appearance', 'header', 'footer', 'mobile'];
    }

    private static function menuItem(bool $withChildren): array
    {
        $fields = [
            TextInput::make('label')->required(),
            TextInput::make('url')->label('Link')->placeholder('/shop'),
            LinkPicker::make('url'),
        ];

        if ($withChildren) {
            $fields[] = Toggle::make('highlight')->label('Highlight (colour + 🔥)')->inline(false);
            $fields[] = Repeater::make('children')->label('Dropdown items')->schema([
                TextInput::make('label')->required(),
                TextInput::make('url')->label('Link'),
                LinkPicker::make('url'),
            ])->columns(3)->defaultItems(0)->collapsible()->collapsed()->columnSpanFull()->addActionLabel('Add dropdown item');
        }

        return $fields;
    }

    protected function fields(): array
    {
        return [
            Tabs::make()->persistTabInQueryString()->tabs([
                Tab::make('Theme')->icon('heroicon-o-swatch')->schema([
                    Section::make('Colours & style')->columns(2)->schema([
                        ColorPicker::make('appearance.primary_color')->label('Brand colour')->helperText('Buttons, prices, badges & links. Choose a shade dark enough for white text (WCAG contrast ≥ 4.5:1) – e.g. #d43d0b.')->required()->regex('/^#[0-9a-fA-F]{3,8}$/'),
                        ColorPicker::make('appearance.accent_color')->label('Dark accent')->helperText('Secondary buttons and dark blocks.')->regex('/^#[0-9a-fA-F]{3,8}$/'),
                        Select::make('appearance.font')->label('Font')->options(collect(config('shop.fonts'))->map(fn ($f) => $f['label'])->all())->helperText('“System” is fastest – no font download.'),
                        Select::make('appearance.radius')->label('Corner style')->options(['none' => 'Square', 'sm' => 'Subtle', 'md' => 'Rounded', 'lg' => 'Soft (default)', 'xl' => 'Extra soft', '2xl' => 'Pill-like']),
                    ]),
                ]),

                Tab::make('Header')->icon('heroicon-o-bars-3')->schema([
                    Section::make('Announcement bar')->columns(3)->schema([
                        Toggle::make('header.topbar_enabled')->label('Show announcement bar')->columnSpanFull(),
                        TextInput::make('header.topbar_text')->label('Text')->columnSpanFull(),
                        ColorPicker::make('header.topbar_bg')->label('Background'),
                        ColorPicker::make('header.topbar_color')->label('Text colour'),
                        Repeater::make('header.topbar_links')->label('Links (right side, desktop)')->schema([
                            TextInput::make('label')->required(), TextInput::make('url')->required(),
                        ])->columns(2)->defaultItems(0)->columnSpanFull(),
                    ]),
                    Section::make('Layout')->columns(2)->schema([
                        Radio::make('header.layout')->label('Header style')->options([
                            'classic' => 'Classic – logo, big search bar, category menu below',
                            'centered' => 'Centered – logo in the middle, menu below',
                            'minimal' => 'Minimal – one row, logo + menu + icons',
                        ])->columnSpanFull(),
                        Toggle::make('header.sticky')->label('Sticky header'),
                        Toggle::make('header.show_search')->label('Search bar'),
                        Toggle::make('header.show_category_menu')->label('“All categories” mega menu'),
                        Toggle::make('header.show_hotline')->label('Hotline number'),
                        Toggle::make('header.show_wishlist')->label('Wishlist icon'),
                        Toggle::make('header.show_account')->label('Account icon'),
                        TextInput::make('header.search_placeholder')->label('Search placeholder')->columnSpanFull(),
                    ]),
                    Section::make('Main menu')->description('Drag to reorder. Each item can have a dropdown.')->schema([
                        Repeater::make('header.menu')->hiddenLabel()->schema(self::menuItem(true))->columns(3)
                            ->collapsible()->itemLabel(fn (array $state) => $state['label'] ?? 'Menu item')->addActionLabel('Add menu item'),
                    ]),
                ]),

                Tab::make('Footer')->icon('heroicon-o-rectangle-group')->schema([
                    Section::make('Style & about')->columns(2)->schema([
                        Select::make('footer.style')->options(['dark' => 'Dark', 'light' => 'Light']),
                        TextInput::make('footer.copyright')->helperText('{year} and {site} are replaced automatically.'),
                        Textarea::make('footer.about')->label('About text')->rows(3)->columnSpanFull(),
                        Toggle::make('footer.show_contact')->label('Show contact column'),
                        Toggle::make('footer.show_newsletter')->label('Show newsletter signup'),
                        TextInput::make('footer.newsletter_title'),
                        TextInput::make('footer.newsletter_text'),
                    ]),
                    Section::make('Link columns')->schema([
                        Repeater::make('footer.columns')->hiddenLabel()->schema([
                            TextInput::make('title')->required(),
                            Repeater::make('links')->schema(self::menuItem(false))->columns(3)->defaultItems(1)->addActionLabel('Add link'),
                        ])->collapsible()->itemLabel(fn (array $state) => $state['title'] ?? 'Column')->maxItems(4)->addActionLabel('Add column'),
                    ]),
                    Section::make('Social media')->columns(3)->schema([
                        TextInput::make('footer.social.facebook')->url()->prefixIcon('heroicon-o-link'),
                        TextInput::make('footer.social.instagram')->url()->prefixIcon('heroicon-o-link'),
                        TextInput::make('footer.social.youtube')->url()->prefixIcon('heroicon-o-link'),
                        TextInput::make('footer.social.tiktok')->url()->prefixIcon('heroicon-o-link'),
                        TextInput::make('footer.social.twitter')->label('X / Twitter')->url()->prefixIcon('heroicon-o-link'),
                        TextInput::make('footer.social.linkedin')->label('LinkedIn')->url()->prefixIcon('heroicon-o-link'),
                    ]),
                    Section::make('Payment badges')->columns(2)->schema([
                        CheckboxList::make('footer.payment_methods')->options(['cod' => 'Cash on Delivery', 'bkash' => 'bKash', 'nagad' => 'Nagad', 'rocket' => 'Rocket', 'visa' => 'VISA', 'mastercard' => 'Mastercard', 'amex' => 'AMEX'])->columns(2),
                        Fields::image('footer.payment_image', 'brand', 'Or upload a payment banner image')->helperText('Replaces the badges when set (e.g. SSLCommerz banner).'),
                    ]),
                ]),

                Tab::make('Mobile')->icon('heroicon-o-device-phone-mobile')->schema([
                    Section::make()->schema([
                        Toggle::make('mobile.bottom_nav')->label('App-style bottom navigation bar')->helperText('Home · Categories · Cart · Wishlist · Account – recommended, increases mobile conversions.'),
                    ]),
                ]),

                Tab::make('Custom code')->icon('heroicon-o-code-bracket')->schema([
                    Section::make()->description('For advanced users. Wrong code can break the layout.')->schema([
                        CodeEditor::make('appearance.custom_css')->label('Custom CSS')->language(Language::Css),
                        CodeEditor::make('appearance.head_scripts')->label('Code before </head>')->language(Language::Html)->helperText('Meta tags, chat widgets, etc.'),
                        CodeEditor::make('appearance.body_scripts')->label('Code before </body>')->language(Language::Html),
                    ]),
                ]),
            ]),
        ];
    }
}
