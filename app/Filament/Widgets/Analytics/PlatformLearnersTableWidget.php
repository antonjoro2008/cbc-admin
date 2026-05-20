<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\Concerns\AdminOnlyWidget;
use App\Services\DashboardAnalyticsService;
use Filament\Actions\Action;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PlatformLearnersTableWidget extends TableWidget
{
    use AdminOnlyWidget;

    protected int | string | array $columnSpan = 'full';

    public static function isDiscovered(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        $records = app(DashboardAnalyticsService::class)->platformLearnerRoster();

        $institutionOptions = collect($records)
            ->pluck('institution_name', 'institution_name')
            ->unique()
            ->sortKeys()
            ->all();

        return $table
            ->heading(null)
            ->description(null)
            ->records(fn (): array => collect($records)
                ->map(fn (array $row): array => array_merge($row, [
                    'key' => (string) $row['student_id'],
                ]))
                ->values()
                ->all())
            ->columns([
                TextColumn::make('name')
                    ->label('Learner')
                    ->weight(FontWeight::Medium)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('institution_name')
                    ->label('Institution')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('admission_number')
                    ->label('Admission #')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('grade_level')
                    ->label('Grade')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('classroom_name')
                    ->label('Class')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('gender')
                    ->label('Gender')
                    ->toggleable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('phone_number')
                    ->label('Phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ->formatStateUsing(fn ($state): string => $state !== null
                        ? number_format((float) $state, 1).'%'
                        : '—')
                    ->color(fn ($state, array $record): ?string => ($record['status'] ?? '') === 'Needs support' ? 'danger' : null)
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('competency_level')
                    ->label('CBE')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('institution_name')
                    ->label('Institution')
                    ->options($institutionOptions)
                    ->attribute('institution_name'),
                SelectFilter::make('status')
                    ->options([
                        'On track' => 'On track',
                        'Needs support' => 'Needs support',
                        'Inactive (30d)' => 'Inactive (30d)',
                        'No attempts yet' => 'No attempts yet',
                    ]),
            ])
            ->recordActions([
                Action::make('viewAnalytics')
                    ->label('View')
                    ->icon('heroicon-o-chart-bar')
                    ->url(fn (array $record): string => UserResource::getUrl('view', [
                        'record' => $record['student_id'],
                    ])),
            ])
            ->defaultSort('average_percent', 'desc')
            ->paginated([10, 25, 50, 100])
            ->striped()
            ->emptyStateHeading('No learners yet')
            ->emptyStateDescription('Students appear here once they register on the platform.');
    }
}
