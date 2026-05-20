<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\AdminOnlyWidget;
use App\Filament\Widgets\Concerns\LazyAnalyticsWidget;
use App\Services\DashboardAnalyticsService;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PlatformTopStudentsWidget extends TableWidget
{
    use AdminOnlyWidget;
    use LazyAnalyticsWidget;

    protected static ?int $sort = -65;

    protected int | string | array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $this->studentTable(
            $table,
            'Top performing students',
            'Highest average scores platform-wide.',
            app(DashboardAnalyticsService::class)->adminAnalytics()['top_performers'] ?? [],
            'top',
        );
    }

    /**
     * @param  list<array<string, mixed>>  $students
     */
    protected function studentTable(Table $table, string $heading, string $description, array $students, string $variant): Table
    {
        return $table
            ->heading($heading)
            ->description($description)
            ->records(fn (): array => collect($students)
                ->map(fn (array $row): array => array_merge($row, [
                    'key' => (string) $row['student_id'],
                ]))
                ->values()
                ->all())
            ->columns([
                TextColumn::make('name')
                    ->label('Student')
                    ->weight(FontWeight::Medium)
                    ->limit(28)
                    ->searchable(),
                TextColumn::make('institution_name')
                    ->label('School')
                    ->limit(20)
                    ->toggleable(),
                TextColumn::make('gender')
                    ->label('Gender')
                    ->toggleable(),
                TextColumn::make('grade_level')
                    ->label('Grade')
                    ->visible($variant === 'support'),
                TextColumn::make('average_percent')
                    ->label('Avg %')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 1).'%')
                    ->color($variant === 'support' ? 'danger' : null)
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('competency_level')
                    ->label('Level')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('average_percent', $variant === 'top' ? 'desc' : 'asc')
            ->paginated([5, 10])
            ->striped();
    }
}
