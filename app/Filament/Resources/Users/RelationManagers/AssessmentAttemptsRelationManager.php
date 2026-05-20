<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\RelationManagers\Concerns\DisplaysRelationManagerAnalytics;
use App\Filament\Widgets\Analytics\StudentCompetencyDistributionChartWidget;
use App\Filament\Widgets\Analytics\StudentOverviewStatsWidget;
use App\Filament\Widgets\Analytics\StudentScoreTrendChartWidget;
use App\Filament\Widgets\Analytics\StudentSubjectPerformanceChartWidget;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class AssessmentAttemptsRelationManager extends RelationManager
{
    use DisplaysRelationManagerAnalytics;

    protected static string $relationship = 'assessmentAttempts';

    /**
     * @return list<class-string>
     */
    protected function relationAnalyticsWidgets(): array
    {
        $owner = $this->getOwnerRecord();

        if (! $owner instanceof User || $owner->user_type !== 'student') {
            return [];
        }

        return [
            StudentOverviewStatsWidget::class,
            StudentScoreTrendChartWidget::class,
            StudentSubjectPerformanceChartWidget::class,
            StudentCompetencyDistributionChartWidget::class,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function relationAnalyticsWidgetData(): array
    {
        return [
            'studentId' => (int) $this->getOwnerRecord()->getKey(),
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
