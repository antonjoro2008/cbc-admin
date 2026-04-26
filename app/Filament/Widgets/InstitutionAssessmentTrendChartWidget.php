<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Services\InstitutionLearnerAnalyticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class InstitutionAssessmentTrendChartWidget extends ChartWidget
{
    protected static ?int $sort = -35;

    protected int | string | array $columnSpan = 'full';

    protected ?string $heading = 'Assessment activity';

    protected ?string $description = 'Completed attempts by your institution\'s learners over the last 14 days.';

    protected string $color = 'primary';

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->isInstitution() && (bool) $user->institution_id;
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        /** @var User $user */
        $user = Auth::user();
        $extras = app(InstitutionLearnerAnalyticsService::class)->institutionAdminExtras((int) $user->institution_id);

        return [
            'labels' => $extras['chart_labels'],
            'datasets' => [
                [
                    'label' => 'Completed assessments',
                    'data' => $extras['chart_values'],
                    'borderColor' => '#705EBC',
                    'backgroundColor' => 'rgba(112, 94, 188, 0.12)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
        ];
    }
}
