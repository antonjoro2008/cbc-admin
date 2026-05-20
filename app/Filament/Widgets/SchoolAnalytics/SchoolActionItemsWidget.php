<?php

namespace App\Filament\Widgets\SchoolAnalytics;

use App\Filament\Widgets\Concerns\ResolvesSchoolAnalytics;
use Filament\Widgets\Widget;

class SchoolActionItemsWidget extends Widget
{
    use ResolvesSchoolAnalytics;

    protected string $view = 'filament.widgets.action-items';

    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        return [
            'heading' => 'Recommended actions',
            'action_items' => $this->schoolAnalytics()['action_items'] ?? [],
        ];
    }
}
