<?php

namespace App\Filament\Support;

use App\Models\Category;
use App\Models\Page;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;

/** A helper select that fills a sibling "url" field with a store link. */
class LinkPicker
{
    public static function make(string $target = 'url'): Select
    {
        return Select::make('_pick_'.$target)
            ->label('Quick link')
            ->placeholder('Pick a page…')
            ->searchable()
            ->dehydrated(false)
            ->live()
            ->options(fn () => [
                'Store' => ['/' => 'Home', '/shop' => 'Shop (all products)', '/shop?on_sale=1' => 'Hot deals', '/shop?sort=latest' => 'New arrivals', '/brands' => 'Brands', '/blog' => 'Blog', '/contact' => 'Contact', '/track-order' => 'Track order', '/wishlist' => 'Wishlist'],
                'Categories' => Category::orderBy('name')->get(['name', 'slug'])->mapWithKeys(fn ($c) => ['/category/'.$c->slug => $c->name])->all(),
                'Pages' => Page::orderBy('title')->get(['title', 'slug'])->mapWithKeys(fn ($p) => ['/'.$p->slug => $p->title])->all(),
            ])
            ->afterStateUpdated(fn ($state, Set $set) => $state ? $set($target, $state) : null);
    }
}
