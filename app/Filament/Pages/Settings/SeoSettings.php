<?php

namespace App\Filament\Pages\Settings;

use App\Filament\Support\Fields;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class SeoSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlassCircle;

    protected static ?string $navigationLabel = 'SEO & tracking';

    protected static ?string $title = 'SEO & tracking';

    protected static ?int $navigationSort = 2;

    protected function groups(): array
    {
        return ['seo'];
    }

    protected function fields(): array
    {
        return [
            Section::make('Home page & defaults')->columns(2)->schema([
                TextInput::make('seo.meta_title')->label('Home page title')->helperText('Shown as “Store name | this text” on Google.')->columnSpanFull(),
                Textarea::make('seo.meta_description')->label('Default meta description')->rows(3)->maxLength(320)->columnSpanFull(),
                TextInput::make('seo.meta_keywords')->label('Keywords'),
                TextInput::make('seo.title_separator')->label('Title separator')->default(' | '),
                Fields::image('seo.og_image', 'brand', 'Social share image')->helperText('1200×630 – used when links are shared on Facebook / WhatsApp.'),
                Select::make('seo.organization_type')->label('Business type (schema.org)')->options(['OnlineStore' => 'Online store', 'Store' => 'Physical + online store', 'Organization' => 'Organization']),
            ]),
            Section::make('Search engine verification')->columns(3)->schema([
                TextInput::make('seo.google_verification')->label('Google Search Console')->placeholder('content value only'),
                TextInput::make('seo.bing_verification')->label('Bing Webmaster'),
                TextInput::make('seo.facebook_domain_verification')->label('Facebook domain'),
            ]),
            Section::make('Analytics & ads')->description('E-commerce events (view item, add to cart, checkout, purchase) are sent automatically.')->columns(3)->schema([
                TextInput::make('seo.gtm_id')->label('Google Tag Manager ID')->placeholder('GTM-XXXXXXX'),
                TextInput::make('seo.ga4_id')->label('Google Analytics 4 ID')->placeholder('G-XXXXXXXXXX')->helperText('Not needed if GA4 is set up inside GTM.'),
                TextInput::make('seo.fb_pixel_id')->label('Facebook Pixel ID'),
            ]),
            Section::make('Indexing')->schema([
                Toggle::make('seo.discourage_indexing')->label('Hide the whole site from search engines')->helperText('Turn on only while the store is under construction.'),
                Textarea::make('seo.robots_extra')->label('Extra robots.txt rules')->rows(3),
                Text::make(new HtmlString(
                    'Your sitemap: <a class="text-primary-600 underline" target="_blank" href="'.route('sitemap').'">'.route('sitemap').'</a> · '
                    .'robots.txt: <a class="text-primary-600 underline" target="_blank" href="'.route('robots').'">'.route('robots').'</a> · '
                    .'Google / Facebook product feed: <a class="text-primary-600 underline" target="_blank" href="'.route('feed.products').'">'.route('feed.products').'</a>'
                )),
            ]),
        ];
    }
}
