<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Services\DashboardAnalyticsService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class InstitutionStudentsNeedingSupportWidget extends Widget
{
    protected static ?int $sort = -25;

    protected string $view = 'filament.widgets.platform-students-table';

    protected int | string | array $columnSpan = 1;

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->isInstitution() && (bool) $user->institution_id;
    }

    protected function getViewData(): array
    {
        $analytics = app(DashboardAnalyticsService::class)->institutionAnalytics(Auth::user());

        return [
            'heading' => 'Learners needing support',
            'description' => 'Below 50% average at your institution.',
            'students' => $analytics['learners_needing_support'] ?? [],
            'variant' => 'support',
        ];
    }
}
