<?php

namespace App\Filament\Resources\Concerns;

use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;

trait DisplaysRecordAnalytics
{
    /**
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    protected function getRecordAnalyticsWidgets(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getRecordAnalyticsWidgetData(): array
    {
        return [];
    }

    /**
     * @return int | array<string, ?int>
     */
    protected function getRecordAnalyticsColumns(): int | array
    {
        return [
            'default' => 1,
            'lg' => 2,
        ];
    }

    public function content(Schema $schema): Schema
    {
        $components = [];

        $widgetClasses = $this->getRecordAnalyticsWidgets();
        $widgets = $this->getWidgetsSchemaComponents($widgetClasses, $this->getRecordAnalyticsWidgetData());

        if ($widgetClasses !== [] && $widgets !== []) {
            $components[] = Grid::make($this->getRecordAnalyticsColumns())
                ->schema($widgets)
                ->columnSpanFull();
        }

        if ($this->hasCombinedRelationManagerTabsWithContent()) {
            $components[] = $this->getRelationManagersContentComponent();
        } else {
            $components[] = $this->hasInfolist()
                ? $this->getInfolistContentComponent()
                : $this->getFormContentComponent();
            $components[] = $this->getRelationManagersContentComponent();
        }

        return $schema->components($components);
    }
}
