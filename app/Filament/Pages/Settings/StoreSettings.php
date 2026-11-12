<?php

namespace App\Filament\Pages\Settings;

use App\Filament\Support\Fields;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

class StoreSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $navigationLabel = 'Store & checkout';

    protected static ?string $title = 'Store & checkout settings';

    protected static ?int $navigationSort = 1;

    protected function groups(): array
    {
        return ['general', 'catalog', 'checkout'];
    }

    protected function fields(): array
    {
        return [
            Tabs::make()->persistTabInQueryString()->tabs([
                Tab::make('Store details')->icon('heroicon-o-building-storefront')->schema([
                    Section::make('Brand')->columns(2)->schema([
                        TextInput::make('general.site_name')->label('Store name')->required(),
                        TextInput::make('general.tagline')->label('Tagline'),
                        Fields::image('general.logo', 'brand', 'Logo')->helperText('PNG/SVG with transparent background.'),
                        Fields::image('general.favicon', 'brand', 'Favicon / app icon')->helperText('Square, at least 192×192.'),
                        TextInput::make('general.logo_height')->label('Logo height (px)')->numeric()->default(36),
                    ]),
                    Section::make('Contact')->columns(2)->schema([
                        TextInput::make('general.phone')->label('Hotline'),
                        TextInput::make('general.whatsapp')->label('WhatsApp number')->helperText('With country code, e.g. 8801712345678'),
                        TextInput::make('general.email')->email(),
                        TextInput::make('general.opening_hours'),
                        Textarea::make('general.address')->rows(2)->columnSpanFull(),
                    ]),
                    Section::make('Currency')->columns(4)->schema([
                        TextInput::make('general.currency_symbol')->label('Symbol')->default('৳'),
                        TextInput::make('general.currency_code')->label('ISO code')->default('BDT')->helperText('Used for Google & Facebook.'),
                        Select::make('general.currency_position')->label('Position')->options(['left' => '৳100', 'right' => '100 ৳']),
                        Select::make('general.decimals')->label('Decimals')->options([0 => 'None (৳1,250)', 2 => 'Two (৳1,250.00)']),
                    ]),
                ]),

                Tab::make('Catalog')->icon('heroicon-o-shopping-bag')->schema([
                    Section::make('Product listing')->columns(3)->schema([
                        TextInput::make('catalog.per_page')->label('Products per page')->numeric()->default(24),
                        TextInput::make('catalog.new_days')->label('“New” badge for (days)')->numeric()->default(21),
                        TextInput::make('catalog.related_count')->label('Related products')->numeric()->default(10),
                        Toggle::make('catalog.show_sold_count')->label('Show “X sold”'),
                        Toggle::make('catalog.show_stock_count')->label('Show low-stock urgency'),
                        TextInput::make('catalog.low_stock_text')->label('Low stock message')->helperText(':count is replaced by the number left.'),
                    ]),
                    Section::make('Product page info')->columns(2)->schema([
                        TextInput::make('catalog.delivery_info')->label('Delivery text'),
                        TextInput::make('catalog.return_info')->label('Return policy text'),
                    ]),
                    Section::make('Reviews')->columns(2)->schema([
                        Toggle::make('catalog.reviews_enabled')->label('Allow customer reviews'),
                        Toggle::make('catalog.reviews_auto_approve')->label('Publish reviews without moderation'),
                    ]),
                ]),

                Tab::make('Checkout')->icon('heroicon-o-shopping-cart')->schema([
                    Section::make('Rules')->columns(3)->schema([
                        TextInput::make('checkout.order_prefix')->label('Order number prefix')->default('ORD')->maxLength(6),
                        TextInput::make('checkout.free_shipping_over')->label('Free delivery over')->numeric()->prefix(fn () => setting('general.currency_symbol'))->helperText('0 = disabled. Drives the progress bar in the cart.'),
                        TextInput::make('checkout.min_order')->label('Minimum order')->numeric()->prefix(fn () => setting('general.currency_symbol')),
                        Toggle::make('checkout.require_email')->label('Email required'),
                        Toggle::make('checkout.show_coupon')->label('Coupon field'),
                        Toggle::make('checkout.show_order_note')->label('Order note field'),
                        Toggle::make('checkout.capture_incomplete')->label('Save abandoned checkouts')->helperText('Saves name & phone typed at checkout so you can call them.')->columnSpanFull(),
                    ]),
                    Section::make('Messages & notifications')->columns(2)->schema([
                        TextInput::make('checkout.admin_email')->label('New-order notification email')->email(),
                        Textarea::make('checkout.success_message')->label('Thank-you message')->rows(2),
                        Textarea::make('checkout.terms_text')->label('Text under “Confirm order”')->rows(2)->columnSpanFull(),
                    ]),
                ]),

                Tab::make('Payments')->icon('heroicon-o-banknotes')->schema([
                    Section::make('Cash on Delivery')->columns(2)->schema([
                        Toggle::make('checkout.cod_enabled')->label('Enable')->live()->columnSpanFull(),
                        TextInput::make('checkout.cod_label')->label('Label')->visible(fn (Get $get) => $get('checkout.cod_enabled'))->dehydratedWhenHidden(),
                        TextInput::make('checkout.cod_note')->label('Instructions')->visible(fn (Get $get) => $get('checkout.cod_enabled'))->dehydratedWhenHidden(),
                    ]),
                    Section::make('bKash (manual)')->columns(3)->schema([
                        Toggle::make('checkout.bkash_enabled')->label('Enable')->live()->columnSpanFull(),
                        TextInput::make('checkout.bkash_number')->label('bKash number')->visible(fn (Get $get) => $get('checkout.bkash_enabled'))->dehydratedWhenHidden(),
                        Select::make('checkout.bkash_type')->label('Account type')->options(['Personal' => 'Personal', 'Merchant' => 'Merchant', 'Agent' => 'Agent'])->visible(fn (Get $get) => $get('checkout.bkash_enabled'))->dehydratedWhenHidden(),
                        Textarea::make('checkout.bkash_note')->label('Instructions')->rows(2)->columnSpanFull()->visible(fn (Get $get) => $get('checkout.bkash_enabled'))->dehydratedWhenHidden(),
                    ]),
                    Section::make('Nagad (manual)')->columns(2)->schema([
                        Toggle::make('checkout.nagad_enabled')->label('Enable')->live()->columnSpanFull(),
                        TextInput::make('checkout.nagad_number')->label('Nagad number')->visible(fn (Get $get) => $get('checkout.nagad_enabled'))->dehydratedWhenHidden(),
                        Textarea::make('checkout.nagad_note')->label('Instructions')->rows(2)->visible(fn (Get $get) => $get('checkout.nagad_enabled'))->dehydratedWhenHidden(),
                    ]),
                    Section::make('Bank transfer')->schema([
                        Toggle::make('checkout.bank_enabled')->label('Enable')->live(),
                        Textarea::make('checkout.bank_details')->label('Bank details shown to customer')->rows(3)->visible(fn (Get $get) => $get('checkout.bank_enabled'))->dehydratedWhenHidden(),
                    ]),
                ]),
            ]),
        ];
    }
}
