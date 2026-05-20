<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Services\DashboardAnalyticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class InstitutionSubjectPerformanceChartWidget extends ChartWidget
{
    protected static ?int $sort = -29;

    protected int | string | array $columnSpan = 'full';

    protected ?string $heading = 'Performance by subject';

    protected ?string $description = 'Average score % across your learners, grouped by subject.';

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
        $subjects = $analytics['subject_breakdown'] ?? [];

        return [
            'labels' => array_column($subjects, 'label'),
            'datasets' => [
                [
                    'label' => 'Average %',
                    'data' => array_column($subjects, 'average_percent'),
                    'backgroundColor' => 'rgba(112, 94, 188, 0.75)',
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
