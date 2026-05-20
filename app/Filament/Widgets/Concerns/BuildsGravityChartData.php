<?php

namespace App\Filament\Widgets\Concerns;

use App\Support\GravityCbcColors;

trait BuildsGravityChartData
{
    /**
     * @param  array{labels?: list<string>, values?: list<float|int>}  $chart
     * @return array<string, mixed>
     */
    protected function barDatasetFromChart(array $chart, string $label = 'Average %', ?string $backgroundColor = null): array
    {
        return [
            'labels' => $chart['labels'] ?? [],
            'datasets' => [
                [
                    'label' => $label,
                    'data' => $chart['values'] ?? [],
                    'backgroundColor' => $backgroundColor ?? GravityCbcColors::rgbaGreen(0.75),
                ],
            ],
        ];
    }

    /**
     * @param  array{labels?: list<string>, values?: list<float|int>}  $chart
     * @return array<string, mixed>
     */
    protected function lineDatasetFromChart(array $chart, string $label = 'Score %'): array
    {
        return [
            'labels' => $chart['labels'] ?? [],
            'datasets' => [
                [
                    'label' => $label,
                    'data' => $chart['values'] ?? [],
                    'borderColor' => GravityCbcColors::GREEN,
                    'backgroundColor' => GravityCbcColors::rgbaGreen(0.12),
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function percentScaleOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'max' => 100,
                ],
            ],
        ];
    }
}
