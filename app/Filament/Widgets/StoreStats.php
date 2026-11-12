<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Filament\Resources\IncompleteOrders\IncompleteOrderResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Products\ProductResource;
use App\Models\IncompleteOrder;
use App\Models\Order;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class StoreStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '60s';

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $valid = fn () => Order::query()->whereNotIn('status', [OrderStatus::Cancelled, OrderStatus::Returned]);

        $days = collect(range(13, 0))->map(fn ($d) => now()->subDays($d)->toDateString());
        $daily = $valid()->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->selectRaw('DATE(created_at) as d, SUM(total) as revenue, COUNT(*) as orders')
            ->groupBy('d')->get()->keyBy('d');

        $revenueTrend = $days->map(fn ($d) => (float) ($daily[$d]->revenue ?? 0))->all();
        $orderTrend = $days->map(fn ($d) => (int) ($daily[$d]->orders ?? 0))->all();

        $today = $valid()->whereDate('created_at', today());
        $todayRevenue = (float) (clone $today)->sum('total');
        $todayOrders = (clone $today)->count();

        $month = $valid()->where('created_at', '>=', now()->startOfMonth());
        $monthRevenue = (float) (clone $month)->sum('total');
        $lastMonthRevenue = (float) $valid()->whereBetween('created_at', [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()])->sum('total');
        $change = $lastMonthRevenue > 0 ? round(($monthRevenue - $lastMonthRevenue) / $lastMonthRevenue * 100) : null;

        $pending = Order::where('status', OrderStatus::Pending)->count();
        $aov = (float) (clone $month)->avg('total');
        $abandoned = IncompleteOrder::where('is_converted', false)->where('created_at', '>=', now()->subDays(7))->count();
        $abandonedValue = (float) IncompleteOrder::where('is_converted', false)->where('created_at', '>=', now()->subDays(7))->sum('total');
        $lowStock = Product::where('is_active', true)->where('track_stock', true)->whereColumn('stock', '<=', 'low_stock_threshold')->count();

        return [
            Stat::make("Today's sales", money($todayRevenue))
                ->description($todayOrders.' '.str('order')->plural($todayOrders).' today')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->chart($revenueTrend)
                ->color('success'),
            Stat::make('This month', money($monthRevenue))
                ->description($change === null ? 'First month of sales' : ($change >= 0 ? "+{$change}% vs last month" : "{$change}% vs last month"))
                ->descriptionIcon($change !== null && $change < 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up')
                ->color($change !== null && $change < 0 ? 'danger' : 'success')
                ->chart($orderTrend),
            Stat::make('Pending orders', $pending)
                ->description($pending ? 'Waiting for your confirmation' : 'All caught up 🎉')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pending ? 'warning' : 'gray')
                ->url(OrderResource::getUrl('index', ['tab' => 'pending'])),
            Stat::make('Avg. order value', money($aov))
                ->description('This month')
                ->descriptionIcon('heroicon-m-calculator'),
            Stat::make('Abandoned checkouts (7d)', $abandoned)
                ->description(money($abandonedValue).' recoverable – call them!')
                ->descriptionIcon('heroicon-m-phone-arrow-up-right')
                ->color($abandoned ? 'danger' : 'gray')
                ->url(IncompleteOrderResource::getUrl()),
            Stat::make('Low / out of stock', $lowStock)
                ->description('Products need restocking')
                ->descriptionIcon('heroicon-m-archive-box-x-mark')
                ->color($lowStock ? 'danger' : 'success')
                ->url(ProductResource::getUrl('index', ['filters' => ['stock_status' => ['value' => 'low']]])),
            Stat::make('Customers', \App\Models\User::where('is_admin', false)->count())
                ->description(Order::distinct('customer_phone')->count('customer_phone').' unique buyers')
                ->descriptionIcon('heroicon-m-users'),
            Stat::make('Delivered (30d)', Order::where('status', OrderStatus::Delivered)->where('created_at', '>=', now()->subDays(30))->count())
                ->description(Order::where('status', OrderStatus::Cancelled)->where('created_at', '>=', now()->subDays(30))->count().' cancelled')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('info'),
        ];
    }
}
