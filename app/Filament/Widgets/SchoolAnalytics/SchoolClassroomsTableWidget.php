<?php

namespace App\Filament\Widgets\SchoolAnalytics;

use App\Filament\Widgets\Concerns\ResolvesInstitutionAnalytics;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class SchoolClassroomsTableWidget extends TableWidget
{
    use ResolvesInstitutionAnalytics;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $rows = $this->institutionAnalytics()['classroom_breakdown'] ?? [];

        return $table
            ->heading('Classrooms')
            ->description(null)
            ->records(fn (): array => collect($rows)
                ->map(fn (array $row, int $index): array => array_merge($row, ['key' => (string) ($row['classroom_id'] ?? $index)]))
                ->values()
                ->all())
            ->columns([
                TextColumn::make('classroom_name')
                    ->label('Class')
                    ->weight(FontWeight::Medium),
                TextColumn::make('grade_level')
                    ->label('Grade'),
                TextColumn::make('student_count')
                    ->label('Students')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('completed_attempts')
                    ->label('Attempts')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('average_percent')
                    ->label('Avg %')
                    ->formatStateUsing(fn ($state): string => $state !== null ? number_format((float) $state, 1).'%' : '—')
                    ->alignEnd(),
                TextColumn::make('competency_level')
                    ->label('CBE')
                    ->placeholder('—'),
            ])
            ->paginated([10, 25])
            ->striped()
            ->emptyStateHeading('No classrooms');
    }
}
