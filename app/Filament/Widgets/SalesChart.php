<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Widgets\ChartWidget;

class SalesChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Revenue – last 30 days';

    protected ?string $maxHeight = '280px';

    protected int|string|array $columnSpan = ['default' => 'full', 'xl' => 2];

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $days = collect(range(29, 0))->map(fn ($d) => now()->subDays($d)->toDateString());
        $rows = Order::query()
            ->whereNotIn('status', [OrderStatus::Cancelled, OrderStatus::Returned])
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->selectRaw('DATE(created_at) as d, SUM(total) as revenue, COUNT(*) as orders')
            ->groupBy('d')->get()->keyBy('d');

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $days->map(fn ($d) => round((float) ($rows[$d]->revenue ?? 0)))->all(),
                    'borderColor' => '#f97316',
                    'backgroundColor' => 'rgba(249,115,22,0.12)',
                    'fill' => true,
                    'tension' => 0.35,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Orders',
                    'data' => $days->map(fn ($d) => (int) ($rows[$d]->orders ?? 0))->all(),
                    'borderColor' => '#0ea5e9',
                    'backgroundColor' => 'transparent',
                    'tension' => 0.35,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $days->map(fn ($d) => \Illuminate\Support\Carbon::parse($d)->format('d M'))->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => true]],
            'scales' => [
                'y' => ['beginAtZero' => true, 'position' => 'left'],
                'y1' => ['beginAtZero' => true, 'position' => 'right', 'grid' => ['drawOnChartArea' => false]],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
