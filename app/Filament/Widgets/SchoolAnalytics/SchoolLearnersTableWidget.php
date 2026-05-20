<?php

namespace App\Filament\Widgets\SchoolAnalytics;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\Concerns\ResolvesInstitutionAnalytics;
use Filament\Actions\Action;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class SchoolLearnersTableWidget extends TableWidget
{
    use ResolvesInstitutionAnalytics;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Learners')
            ->description(null)
            ->records(fn (): array => $this->learnerRecords())
            ->columns([
                TextColumn::make('name')
                    ->label('Student')
                    ->weight(FontWeight::Medium)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('admission_number')
                    ->label('Admission #')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('grade_level')
                    ->label('Grade')
                    ->sortable(),
                TextColumn::make('classroom_name')
                    ->label('Class')
                    ->placeholder('—'),
                TextColumn::make('gender')
                    ->label('Gender')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'On track' => 'success',
                        'Needs support' => 'danger',
                        'Inactive (30d)' => 'gray',
                        default => 'warning',
                    })
                    ->sortable(),
                TextColumn::make('completed_attempts')
                    ->label('Attempts')
                    ->numeric()
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('average_percent')
                    ->label('Avg %')
                    ->formatStateUsing(fn ($state, array $record): string => $state !== null
                        ? number_format((float) $state, 1).'%'
                        : '—')
                    ->color(fn ($state, array $record): ?string => ($record['status'] ?? '') === 'Needs support' ? 'danger' : null)
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('competency_level')
                    ->label('CBE')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->recordActions([
                Action::make('viewPerformance')
                    ->label('Charts')
                    ->icon('heroicon-o-chart-bar')
                    ->url(fn (array $record): string => UserResource::getUrl('view', [
                        'record' => $record['student_id'],
                    ])),
            ])
            ->defaultSort('average_percent', 'desc')
            ->paginated([10, 25, 50])
            ->striped()
            ->emptyStateHeading('No learners')
            ->emptyStateDescription('Students appear once they are linked to this school.');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function learnerRecords(): array
    {
        $analytics = $this->institutionAnalytics();
        $inactiveIds = collect($analytics['inactive_learners'] ?? [])->pluck('student_id')->flip();

        return collect($analytics['student_roster'] ?? [])
            ->map(function (array $row) use ($inactiveIds): array {
                $attempts = (int) ($row['completed_attempts'] ?? 0);
                $avg = $row['average_percent'] ?? null;

                if ($inactiveIds->has($row['student_id'] ?? null)) {
                    $status = 'Inactive (30d)';
                } elseif ($attempts === 0) {
                    $status = 'No attempts yet';
                } elseif ($avg !== null && (float) $avg < 50) {
                    $status = 'Needs support';
                } else {
                    $status = 'On track';
                }

                return array_merge($row, [
                    'key' => (string) ($row['student_id'] ?? $row['admission_number'] ?? $row['name']),
                    'status' => $status,
                ]);
            })
            ->values()
            ->all();
    }
}
