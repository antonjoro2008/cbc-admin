<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Services\DashboardAnalyticsService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class InstitutionActionItemsWidget extends Widget
{
    protected static ?int $sort = -27;

    protected string $view = 'filament.widgets.action-items';

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->isInstitution() && (bool) $user->institution_id;
    }

    protected function getViewData(): array
    {
        $analytics = app(DashboardAnalyticsService::class)->institutionAnalytics(Auth::user());

        return [
            'heading' => 'Recommended actions for your institution',
            'action_items' => $analytics['action_items'] ?? [],
        ];
    }
}
