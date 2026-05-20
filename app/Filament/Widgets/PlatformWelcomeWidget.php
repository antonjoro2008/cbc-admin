<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\AdminOnlyWidget;
use App\Services\DashboardAnalyticsService;
use Filament\Widgets\Widget;

class PlatformWelcomeWidget extends Widget
{
    use AdminOnlyWidget;

    protected static ?int $sort = -100;

    protected string $view = 'filament.widgets.platform-welcome';

    protected int | string | array $columnSpan = 'full';
}
