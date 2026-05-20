<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Services\DashboardAnalyticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class InstitutionGenderCohortChartWidget extends ChartWidget
{
    protected static ?int $sort = -34;

    protected int | string | array $columnSpan = 1;

    protected ?string $heading = 'Learners by gender';

    protected ?string $description = 'Your institution roster segmented by recorded gender.';

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->isInstitution() && (bool) $user->institution_id;
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $service = app(DashboardAnalyticsService::class);
        $inclusion = $service->inclusionMetricsForScope((int) Auth::user()->institution_id);
        $chart = $service->genderCohortChart($inclusion['cohort_by_gender'] ?? []);

        return [
            'labels' => $chart['labels'],
            'datasets' => [
                [
                    'data' => $chart['values'],
                    'backgroundColor' => $chart['colors'],
                ],
            ],
        ];
    }
}
