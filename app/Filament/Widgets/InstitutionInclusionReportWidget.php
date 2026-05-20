<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Services\DashboardAnalyticsService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class InstitutionInclusionReportWidget extends Widget
{
    protected static ?int $sort = -30;

    protected string $view = 'filament.widgets.inclusion-report';

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->isInstitution() && (bool) $user->institution_id;
    }

    protected function getViewData(): array
    {
        $service = app(DashboardAnalyticsService::class);
        $inclusion = $service->inclusionMetricsForScope((int) Auth::user()->institution_id);

        return array_merge($service->formatInclusionForView($inclusion), [
            'scope_label' => 'institution',
            'show_color_legend' => true,
            'heading' => 'Inclusion & equity snapshot',
            'description' => 'Roster counts by recorded gender, reporting coverage, and average outcomes for your institution (CBC / CBE reporting view).',
        ]);
    }
}
