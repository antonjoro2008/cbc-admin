<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\AdminOnlyWidget;
use App\Filament\Widgets\Concerns\LazyAnalyticsWidget;
use App\Services\DashboardAnalyticsService;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PlatformClassroomComparisonWidget extends TableWidget
{
    use AdminOnlyWidget;
    use LazyAnalyticsWidget;

    protected static ?int $sort = -72;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('All classrooms across schools')
            ->description('Every class group on the platform — school, grade, students, attempts, and average CBE outcome.')
            ->records(fn (): array => collect(app(DashboardAnalyticsService::class)->adminAnalytics()['classroom_breakdown'] ?? [])
                ->map(fn (array $row): array => [
                    'key' => (string) $row['classroom_id'],
                    'institution_name' => $row['institution_name'],
                    'classroom_name' => $row['classroom_name'],
                    'grade_level' => $row['grade_level'],
                    'student_count' => $row['student_count'],
                    'completed_attempts' => $row['completed_attempts'],
                    'average_percent' => $row['average_percent'],
                    'competency_level' => $row['competency_level'],
                ])
                ->values()
                ->all())
            ->columns([
                TextColumn::make('institution_name')
                    ->label('School')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('classroom_name')
                    ->label('Classroom')
                    ->weight(FontWeight::Medium)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('grade_level')
                    ->label('Grade')
                    ->placeholder('—'),
                TextColumn::make('student_count')
                    ->label('Students')
                    ->numeric()
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('completed_attempts')
                    ->label('Attempts')
                    ->numeric()
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('average_percent')
                    ->label('Avg %')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 1).'%')
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('competency_level')
                    ->label('CBE level')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('average_percent', 'desc')
            ->paginated([10, 25, 50])
            ->striped();
    }
}
