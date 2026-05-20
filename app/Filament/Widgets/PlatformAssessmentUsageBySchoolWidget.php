<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\AdminOnlyWidget;
use App\Filament\Widgets\Concerns\LazyAnalyticsWidget;
use App\Services\DashboardAnalyticsService;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PlatformAssessmentUsageBySchoolWidget extends TableWidget
{
    use AdminOnlyWidget;
    use LazyAnalyticsWidget;

    protected static ?int $sort = -67;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $schools = app(DashboardAnalyticsService::class)->adminAnalytics()['assessment_usage_by_school'] ?? [];

        return $table
            ->heading('Assessment usage by school')
            ->description('Completed attempts and distinct assessments used per institution.')
            ->records(fn (): array => collect($schools)
                ->map(fn (array $row): array => [
                    'key' => (string) ($row['school_id'] ?? $row['school_name']),
                    'school_name' => $row['school_name'],
                    'distinct_assessments_used' => $row['distinct_assessments_used'],
                    'total_completed_attempts' => $row['total_completed_attempts'],
                ])
                ->values()
                ->all())
            ->columns([
                TextColumn::make('school_name')
                    ->label('School')
                    ->weight(FontWeight::Medium)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('distinct_assessments_used')
                    ->label('Assessments used')
                    ->numeric()
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('total_completed_attempts')
                    ->label('Completed attempts')
                    ->numeric()
                    ->alignEnd()
                    ->sortable(),
            ])
            ->defaultSort('total_completed_attempts', 'desc')
            ->paginated([10, 25, 50])
            ->striped();
    }
}
