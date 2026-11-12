<?php

namespace App\Filament\Pages\Settings;

use App\Filament\Support\Fields;
use App\Support\Icon;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;

class SalesBoosters extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRocketLaunch;

    protected static ?string $navigationLabel = 'Sales boosters';

    protected static ?string $title = 'Sales boosters';

    protected static string|\UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 0;

    protected function groups(): array
    {
        return ['conversion'];
    }

    protected function fields(): array
    {
        return [
            Section::make('Product page')->description('Tools that turn visitors into buyers.')->columns(2)->schema([
                Toggle::make('conversion.buy_now')->label('“Buy Now” button (skip the cart)'),
                Toggle::make('conversion.sticky_add_to_cart')->label('Sticky buy bar on mobile'),
                Toggle::make('conversion.whatsapp_order')->label('“Order on WhatsApp” button'),
                Toggle::make('conversion.call_to_order')->label('“Call to order” button'),
                Toggle::make('conversion.recently_viewed')->label('Recently viewed products'),
            ]),
            Section::make('WhatsApp chat button')->columns(2)->schema([
                Toggle::make('conversion.whatsapp_button')->label('Floating WhatsApp button')->helperText('Uses the WhatsApp number from Store settings.'),
                TextInput::make('conversion.whatsapp_message')->label('Pre-filled message'),
            ]),
            Section::make('Social proof')->columns(2)->schema([
                Toggle::make('conversion.sales_popup')->label('“Someone just bought…” popup')->helperText('Shows REAL orders from the last 7 days (first name + city only).'),
                TextInput::make('conversion.sales_popup_interval')->label('Seconds between popups')->numeric()->default(18),
            ]),
            Section::make('Exit-intent offer')->description('Shown once when a visitor is about to leave (desktop) or after 45s (mobile).')->columns(2)->schema([
                Toggle::make('conversion.exit_popup')->label('Enable')->columnSpanFull(),
                TextInput::make('conversion.exit_popup_title')->label('Title'),
                TextInput::make('conversion.exit_popup_coupon')->label('Coupon code to show')->helperText('Create the coupon under Marketing → Coupons.'),
                Textarea::make('conversion.exit_popup_text')->label('Text')->rows(2),
                Fields::image('conversion.exit_popup_image', 'banners', 'Image (optional)'),
            ]),
            Section::make('Trust badges')->description('Shown above the footer on every page.')->schema([
                Repeater::make('conversion.trust_badges')->hiddenLabel()->schema([
                    Select::make('icon')->options(Icon::choices())->required(),
                    TextInput::make('text')->required(),
                ])->columns(2)->maxItems(4)->addActionLabel('Add badge'),
            ]),
        ];
    }
}
