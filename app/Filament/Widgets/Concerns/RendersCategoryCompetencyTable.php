<?php

namespace App\Filament\Widgets\Concerns;

use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

trait RendersCategoryCompetencyTable
{
    /**
     * @param  list<array<string, mixed>>  $records
     */
    protected function categoryCompetencyTable(
        Table $table,
        string $heading,
        string $description,
        array $records,
        string $sortDirection,
        ?string $percentColor = null,
    ): Table {
        return $table
            ->heading($heading)
            ->description($description)
            ->records(fn (): array => collect($records)
                ->values()
                ->map(fn (array $row, int $index): array => [
                    'key' => (string) ($row['label'] ?? $index),
                    'label' => $row['label'] ?? '—',
                    'average_percent' => $row['average_percent'] ?? 0,
                    'competency_level' => $row['competency_level'] ?? '—',
                    'questions_answered' => $row['questions_answered'] ?? 0,
                ])
                ->all())
            ->columns([
                TextColumn::make('label')
                    ->label('Competency area')
                    ->weight(FontWeight::Medium)
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('average_percent')
                    ->label('Avg score')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 1).'%')
                    ->color($percentColor)
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
                    })
                    ->sortable(),
                TextColumn::make('questions_answered')
                    ->label('Marked answers')
                    ->numeric()
                    ->alignEnd()
                    ->sortable(),
            ])
            ->defaultSort('average_percent', $sortDirection)
            ->paginated([5, 10])
            ->striped()
            ->emptyStateHeading('No marked competency data yet')
            ->emptyStateDescription('Category insights appear once learners complete assessments with marked feedback.');
    }
}
