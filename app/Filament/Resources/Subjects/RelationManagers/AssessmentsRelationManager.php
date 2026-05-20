<?php

namespace App\Filament\Resources\Subjects\RelationManagers;

use App\Filament\Resources\Assessments\AssessmentResource;
use App\Filament\Resources\RelationManagers\Concerns\DisplaysRelationManagerAnalytics;
use App\Filament\Support\SubjectAnalyticsWidgets;
use App\Models\Subject;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class AssessmentsRelationManager extends RelationManager
{
    use DisplaysRelationManagerAnalytics;

    protected static string $relationship = 'assessments';

    protected static ?string $relatedResource = AssessmentResource::class;

    /**
     * @return list<class-string>
     */
    protected function relationAnalyticsWidgets(): array
    {
        $owner = $this->getOwnerRecord();

        if (! $owner instanceof Subject) {
            return [];
        }

        return SubjectAnalyticsWidgets::forSubject((int) $owner->getKey());
    }

    /**
     * @return array<string, mixed>
     */
    protected function relationAnalyticsWidgetData(): array
    {
        return [
            'subjectId' => (int) $this->getOwnerRecord()->getKey(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
