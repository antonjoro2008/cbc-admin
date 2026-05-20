<?php

namespace App\Filament\Resources\Subjects\Pages;

use App\Filament\Resources\Concerns\DisplaysRecordAnalytics;
use App\Filament\Resources\Subjects\SubjectResource;
use App\Filament\Support\SubjectAnalyticsWidgets;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;

class ViewSubject extends ViewRecord
{
    use DisplaysRecordAnalytics;

    protected static string $resource = SubjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getRecordAnalyticsWidgetData(): array
    {
        return [
            'subjectId' => (int) $this->getRecord()->getKey(),
        ];
    }

    /**
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    protected function getRecordAnalyticsWidgets(): array
    {
        return SubjectAnalyticsWidgets::forSubject((int) $this->getRecord()->getKey());
    }
}
