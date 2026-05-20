<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Services\DashboardAnalyticsService;
use App\Support\GravityCbcColors;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class InstitutionCompetencyDistributionChartWidget extends ChartWidget
{
    protected static ?int $sort = -31;

    protected int | string | array $columnSpan = 1;

    protected ?string $heading = 'CBE competency distribution';

    protected ?string $description = 'Completed attempts by CBC/CBE level for your learners.';

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
        $dist = $analytics['competency_distribution'] ?? ['BE' => 0, 'AE' => 0, 'ME' => 0, 'EE' => 0];

        return [
            'labels' => ['Below (BE)', 'Approaching (AE)', 'Meeting (ME)', 'Exceeding (EE)'],
            'datasets' => [
                [
                    'label' => 'Attempts',
                    'data' => array_values($dist),
                    'backgroundColor' => GravityCbcColors::competencyBands(),
                ],
            ],
        ];
    }
}
