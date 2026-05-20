<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Services\DashboardAnalyticsService;
use App\Support\GravityCbcColors;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class InstitutionClassroomComparisonChartWidget extends ChartWidget
{
    protected static ?int $sort = -28;

    protected int | string | array $columnSpan = 'full';

    protected ?string $heading = 'Performance by classroom';

    protected ?string $description = 'Compare average CBE outcomes across classes at your institution.';

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
        $classrooms = $analytics['classroom_breakdown'] ?? [];

        return [
            'labels' => array_column($classrooms, 'classroom_name'),
            'datasets' => [
                [
                    'label' => 'Average %',
                    'data' => array_column($classrooms, 'average_percent'),
                    'backgroundColor' => GravityCbcColors::rgbaBlue(0.75),
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
