<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\AdminOnlyWidget;
use App\Filament\Widgets\Concerns\LazyAnalyticsWidget;
use App\Services\DashboardAnalyticsService;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PlatformInstitutionComparisonWidget extends TableWidget
{
    use AdminOnlyWidget;
    use LazyAnalyticsWidget;

    protected static ?int $sort = -70;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('All schools & learner groups')
            ->description('Every institution plus individual learners — students, attempts, average CBE outcome, and gender data coverage.')
            ->records(fn (): array => collect(app(DashboardAnalyticsService::class)->adminAnalytics()['institution_breakdown'] ?? [])
                ->map(fn (array $row): array => [
                    'key' => (string) ($row['institution_id'] ?? 'individual'),
                    'name' => $row['name'],
                    'student_count' => $row['student_count'],
                    'completed_attempts' => $row['completed_attempts'],
                    'average_percent' => $row['average_percent'],
                    'competency_level' => $row['competency_level'],
                    'gender_reporting_percent' => $row['gender_reporting_percent'],
                ])
                ->values()
                ->all())
            ->columns([
                TextColumn::make('name')
                    ->label('School / group')
                    ->weight(FontWeight::Medium)
                    ->searchable()
                    ->sortable(),
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
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_contains($state, 'EE') => 'success',
                        str_contains($state, 'ME') => 'info',
                        str_contains($state, 'AE') => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('gender_reporting_percent')
                    ->label('Gender data')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 1).'%')
                    ->alignEnd()
                    ->sortable(),
            ])
            ->defaultSort('average_percent', 'desc')
            ->paginated([10, 25, 50])
            ->striped();
    }
}
