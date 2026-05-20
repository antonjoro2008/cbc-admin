<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\AdminOnlyWidget;
use App\Models\Payment;
use App\Support\GravityCbcColors;
use Filament\Widgets\ChartWidget;

class RevenueByMonth extends ChartWidget
{
    use AdminOnlyWidget;

    protected static ?int $sort = -50;

    protected ?string $heading = 'Revenue By Month';

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $months = collect();
        $revenue = collect();

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthName = $date->format('M Y');

            $monthlyRevenue = Payment::where('status', 'successful')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->sum('amount');

            $months->push($monthName);
            $revenue->push($monthlyRevenue);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (KES)',
                    'data' => $revenue->toArray(),
                    'borderColor' => GravityCbcColors::GREEN,
                    'backgroundColor' => GravityCbcColors::rgbaGreen(0.1),
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $months->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return "KES " + value.toLocaleString(); }',
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) { return "Revenue: KES " + context.parsed.y.toLocaleString(); }',
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}
