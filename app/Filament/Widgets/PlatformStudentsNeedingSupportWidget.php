<?php

namespace App\Filament\Widgets;

use App\Services\DashboardAnalyticsService;
use Filament\Tables\Table;

class PlatformStudentsNeedingSupportWidget extends PlatformTopStudentsWidget
{
    protected static ?int $sort = -64;

    public function table(Table $table): Table
    {
        return $this->studentTable(
            $table,
            'Students needing support',
            'Below 50% average — candidates for intervention.',
            app(DashboardAnalyticsService::class)->adminAnalytics()['learners_needing_support'] ?? [],
            'support',
        );
    }
}
