<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Services\DashboardAnalyticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class InstitutionGenderPerformanceChartWidget extends ChartWidget
{
    protected static ?int $sort = -33;

    protected int | string | array $columnSpan = 1;

    protected ?string $heading = 'Performance by gender';

    protected ?string $description = 'Average score % from completed attempts, by gender segment.';

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
        $service = app(DashboardAnalyticsService::class);
        $inclusion = $service->inclusionMetricsForScope((int) Auth::user()->institution_id);
        $chart = $service->genderPerformanceChart($inclusion['performance_by_gender'] ?? []);

        return [
            'labels' => $chart['labels'],
            'datasets' => [
                [
                    'label' => 'Average %',
                    'data' => $chart['values'],
                    'backgroundColor' => $chart['colors'],
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
