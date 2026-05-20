<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Services\DashboardAnalyticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class InstitutionCategoryPerformanceChartWidget extends ChartWidget
{
    protected static ?int $sort = -24;

    protected int | string | array $columnSpan = 'full';

    protected ?string $heading = 'Performance by competency area';

    protected ?string $description = 'Average scores from marked answers, grouped by question category tag.';

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->isInstitution() && (bool) $user->institution_id;
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $analytics = app(DashboardAnalyticsService::class)->institutionAnalytics(Auth::user());
        $chart = $analytics['charts']['category_performance'] ?? ['labels' => [], 'values' => []];

        return [
            'labels' => $chart['labels'],
            'datasets' => [
                [
                    'label' => 'Average %',
                    'data' => $chart['values'],
                    'backgroundColor' => 'rgba(245, 158, 11, 0.75)',
                ],
            ],
        ];
    }

    protected function getOptions(): array
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
