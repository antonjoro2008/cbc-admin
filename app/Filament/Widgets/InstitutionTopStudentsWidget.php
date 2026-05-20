<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Services\DashboardAnalyticsService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class InstitutionTopStudentsWidget extends Widget
{
    protected static ?int $sort = -26;

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
            'heading' => 'Top performers',
            'description' => 'Highest average scores at your institution.',
            'students' => $analytics['top_performers'] ?? [],
            'variant' => 'top',
        ];
    }
}
