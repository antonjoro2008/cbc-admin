<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Services\DashboardAnalyticsService;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class InstitutionTopStudentsWidget extends PlatformTopStudentsWidget
{
    protected static ?int $sort = -26;

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->isInstitution() && (bool) $user->institution_id;
    }

    public static function getSort(): int
    {
        return -26;
    }

    public function table(Table $table): Table
    {
        $analytics = app(DashboardAnalyticsService::class)->institutionAnalytics(Auth::user());

        return $this->studentTable(
            $table,
            'Top performers',
            'Highest average scores at your institution.',
            $analytics['top_performers'] ?? [],
            'top',
        );
    }
}
