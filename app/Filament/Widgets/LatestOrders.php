<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestOrders extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = ['default' => 'full', 'xl' => 2];

    protected static ?string $heading = 'Latest orders';

    public function table(Table $table): Table
    {
        return $table
            ->query(Order::query()->withCount('items')->latest()->limit(8))
            ->paginated(false)
            ->columns([
                TextColumn::make('order_number')->label('Order')->weight('bold')->description(fn (Order $r) => $r->created_at->diffForHumans()),
                TextColumn::make('customer_name')->label('Customer')->description(fn (Order $r) => $r->customer_phone),
                TextColumn::make('city'),
                TextColumn::make('total')->formatStateUsing(fn ($state) => money($state)),
                TextColumn::make('status')->badge(),
            ])
            ->recordUrl(fn (Order $r) => OrderResource::getUrl('view', ['record' => $r]))
            ->headerActions([
                Action::make('all')->label('View all')->url(OrderResource::getUrl())->link(),
            ]);
    }
}
