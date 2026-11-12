<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class TopProducts extends TableWidget
{
    protected static ?int $sort = 5;

    protected static ?string $heading = 'Best sellers';

    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 1];

    public function table(Table $table): Table
    {
        return $table
            ->query(Product::query()->where('is_active', true)->orderByDesc('sold_count')->limit(6))
            ->paginated(false)
            ->columns([
                ImageColumn::make('thumbnail')->label('')->disk('public')->imageSize(36)->square(),
                TextColumn::make('name')->limit(28)->description(fn (Product $r) => 'Stock: '.$r->stock),
                TextColumn::make('sold_count')->label('Sold')->badge()->color('success'),
            ])
            ->recordUrl(fn (Product $r) => ProductResource::getUrl('edit', ['record' => $r]));
    }
}
