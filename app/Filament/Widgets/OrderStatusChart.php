<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Widgets\ChartWidget;

class OrderStatusChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Orders by status (30 days)';

    protected ?string $maxHeight = '280px';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $counts = Order::where('created_at', '>=', now()->subDays(30))
            ->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');

        $colors = ['pending' => '#f59e0b', 'confirmed' => '#0ea5e9', 'processing' => '#6366f1', 'shipped' => '#06b6d4', 'delivered' => '#10b981', 'cancelled' => '#ef4444', 'returned' => '#94a3b8'];
        $statuses = collect(OrderStatus::cases())->filter(fn ($s) => ($counts[$s->value] ?? 0) > 0);

        return [
            'datasets' => [[
                'data' => $statuses->map(fn ($s) => (int) $counts[$s->value])->values()->all(),
                'backgroundColor' => $statuses->map(fn ($s) => $colors[$s->value])->values()->all(),
            ]],
            'labels' => $statuses->map(fn ($s) => $s->getLabel())->values()->all(),
        ];
    }

    protected function getOptions(): array
    {
        return ['plugins' => ['legend' => ['position' => 'bottom']], 'scales' => ['x' => ['display' => false], 'y' => ['display' => false]]];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
