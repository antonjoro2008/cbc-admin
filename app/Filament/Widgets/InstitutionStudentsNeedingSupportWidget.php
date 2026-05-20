<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Services\DashboardAnalyticsService;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class InstitutionStudentsNeedingSupportWidget extends PlatformTopStudentsWidget
{
    protected static ?int $sort = -25;

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->isInstitution() && (bool) $user->institution_id;
    }

    public static function getSort(): int
    {
        return -25;
    }

    public function table(Table $table): Table
    {
        $analytics = app(DashboardAnalyticsService::class)->institutionAnalytics(Auth::user());

        return $this->studentTable(
            $table,
            'Learners needing support',
            'Below 50% average at your institution.',
            $analytics['learners_needing_support'] ?? [],
            'support',
        );
    }
}
