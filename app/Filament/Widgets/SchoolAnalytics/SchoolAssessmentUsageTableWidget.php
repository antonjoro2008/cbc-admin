<?php

namespace App\Filament\Widgets\SchoolAnalytics;

use App\Filament\Widgets\Concerns\ResolvesInstitutionAnalytics;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class SchoolAssessmentUsageTableWidget extends TableWidget
{
    use ResolvesInstitutionAnalytics;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $rows = $this->institutionAnalytics()['assessment_usage'] ?? [];

        return $table
            ->heading('Assessment usage')
            ->description(null)
            ->records(fn (): array => collect($rows)
                ->map(fn (array $row): array => array_merge($row, [
                    'key' => (string) ($row['assessment_id'] ?? $row['assessment_name']),
                ]))
                ->values()
                ->all())
            ->columns([
                TextColumn::make('assessment_name')
                    ->label('Assessment')
                    ->weight(FontWeight::Medium)
                    ->searchable()
                    ->wrap(),
                TextColumn::make('total_attempts')
                    ->label('Attempts')
                    ->numeric()
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('completed_attempts')
                    ->label('Completed')
                    ->numeric()
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('average_score_percent')
                    ->label('Avg %')
                    ->formatStateUsing(fn ($state): string => $state !== null ? number_format((float) $state, 1).'%' : '—')
                    ->alignEnd()
                    ->sortable(),
            ])
            ->defaultSort('completed_attempts', 'desc')
            ->paginated([10, 25])
            ->striped()
            ->emptyStateHeading('No assessment usage yet');
    }
}
