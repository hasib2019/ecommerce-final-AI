<?php

namespace App\Filament\Support;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/** Reusable form building blocks for the admin panel. */
class Fields
{
    public static function image(string $name, string $directory, ?string $label = null): FileUpload
    {
        return FileUpload::make($name)
            ->label($label)
            ->image()
            ->disk('public')
            ->directory($directory)
            ->visibility('public')
            ->maxSize(5120)
            ->imageEditor()
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml']);
    }

    /** Name field that auto-fills the slug while creating. */
    public static function nameWithSlug(string $name = 'name', string $label = 'Name'): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->required()
            ->maxLength(255)
            ->live(onBlur: true)
            ->afterStateUpdated(function (?string $state, Set $set, Get $get, string $operation) {
                if ($operation === 'create' || blank($get('slug'))) {
                    $set('slug', Str::slug((string) $state));
                }
            });
    }

    public static function slug(string $prefix = ''): TextInput
    {
        return TextInput::make('slug')
            ->label('URL slug')
            ->helperText('Leave empty to generate automatically. Changing it later changes the page URL – add a redirect for SEO.')
            ->prefix(url($prefix).'/')
            ->maxLength(255)
            ->alphaDash();
    }

    /** SEO section with live Google snippet preview. */
    public static function seo(string $titleSource = 'name', string $descriptionSource = 'short_description'): Section
    {
        return Section::make('Search engine listing (SEO)')
            ->description('Control how this page appears on Google. Empty fields are generated automatically.')
            ->icon('heroicon-o-magnifying-glass')
            ->collapsible()
            ->collapsed()
            ->schema([
                Text::make(fn (Get $get) => new HtmlString(
                    '<div style="font-family:arial,sans-serif;max-width:600px;padding:12px 14px;border:1px solid rgb(0 0 0 / .08);border-radius:10px;background:#fff">'
                    .'<div style="font-size:12px;color:#202124">'.e(parse_url(url('/'), PHP_URL_HOST)).' › '.e($get('slug') ?: '…').'</div>'
                    .'<div style="font-size:18px;color:#1a0dab;line-height:1.3;margin:2px 0">'.e(Str::limit($get('meta_title') ?: ($get($titleSource) ?: 'Page title'), 60)).' | '.e(setting('general.site_name')).'</div>'
                    .'<div style="font-size:13px;color:#4d5156;line-height:1.5">'.e(Str::limit(strip_tags($get('meta_description') ?: ($get($descriptionSource) ?: 'Meta description will be generated from the content.')), 158)).'</div></div>'
                )),
                TextInput::make('meta_title')
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->helperText(fn (?string $state) => mb_strlen((string) $state).' / 60 characters recommended'),
                Textarea::make('meta_description')
                    ->rows(3)
                    ->maxLength(500)
                    ->live(onBlur: true)
                    ->helperText(fn (?string $state) => mb_strlen((string) $state).' / 155 characters recommended'),
            ]);
    }
}
