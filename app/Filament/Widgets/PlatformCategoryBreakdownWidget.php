<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\AdminOnlyWidget;
use Filament\Widgets\Widget;

/**
 * @deprecated Replaced by PlatformCompetencyStrengthsTableWidget and PlatformAreasNeedingFocusTableWidget.
 *             Kept so stale Filament component caches do not break the dashboard.
 */
class PlatformCategoryBreakdownWidget extends Widget
{
    use AdminOnlyWidget;

    protected static ?int $sort = -69;

    protected int | string | array $columnSpan = 'full';

    protected string $view = 'filament.widgets.deprecated-widget-placeholder';

    public static function canView(): bool
    {
        return false;
    }
}
