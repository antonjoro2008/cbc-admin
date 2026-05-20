<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\AdminOnlyWidget;
use App\Services\DashboardAnalyticsService;
use Filament\Widgets\Widget;

class PlatformTopStudentsWidget extends Widget
{
    use AdminOnlyWidget;

    protected static ?int $sort = -65;

    protected string $view = 'filament.widgets.platform-students-table';

    protected int | string | array $columnSpan = 1;

    protected function getViewData(): array
    {
        return [
            'heading' => 'Top performing students',
            'description' => 'Highest average scores platform-wide.',
            'students' => app(DashboardAnalyticsService::class)->platformStudentSummaries(15)['top'],
            'variant' => 'top',
        ];
    }
}
