<?php

namespace App\Filament\Widgets\SchoolAnalytics;

use App\Filament\Widgets\Concerns\ResolvesInstitutionAnalytics;
use Filament\Widgets\Widget;

class SchoolActionItemsWidget extends Widget
{
    use ResolvesInstitutionAnalytics;

    protected string $view = 'filament.widgets.action-items';

    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        return [
            'heading' => 'Recommended actions',
            'action_items' => $this->institutionAnalytics()['action_items'] ?? [],
        ];
    }
}
