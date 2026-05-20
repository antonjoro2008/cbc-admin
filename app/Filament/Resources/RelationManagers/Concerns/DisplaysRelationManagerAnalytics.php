<?php

namespace App\Filament\Resources\RelationManagers\Concerns;

use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;

trait DisplaysRelationManagerAnalytics
{
    /**
     * @return list<class-string>
     */
    protected function relationAnalyticsWidgets(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function relationAnalyticsWidgetData(): array
    {
        return [];
    }

    /**
     * @return int | array<string, ?int>
     */
    protected function relationAnalyticsColumns(): int | array
    {
        return [
            'default' => 1,
            'lg' => 2,
        ];
    }

    protected function shouldShowRelationAnalytics(): bool
    {
        return is_subclass_of($this->getPageClass(), ViewRecord::class)
            && $this->relationAnalyticsWidgets() !== [];
    }

    public function content(Schema $schema): Schema
    {
        $components = [
            $this->getTabsContentComponent(),
            RenderHook::make(PanelsRenderHook::RESOURCE_RELATION_MANAGER_BEFORE),
        ];

        if ($this->shouldShowRelationAnalytics()) {
            $data = $this->relationAnalyticsWidgetData();
            $livewireComponents = [];

            foreach ($this->relationAnalyticsWidgets() as $index => $widgetClass) {
                $key = class_basename($widgetClass).'-'.$index.'-'.md5(json_encode($data));
                $livewireComponents[] = Livewire::make(
                    $widgetClass,
                    fn (): array => $data,
                )->key($key);
            }

            $components[] = Grid::make($this->relationAnalyticsColumns())
                ->schema($livewireComponents)
                ->columnSpanFull();
        }

        $components[] = EmbeddedTable::make();
        $components[] = RenderHook::make(PanelsRenderHook::RESOURCE_RELATION_MANAGER_AFTER);

        return $schema->components($components);
    }
}
