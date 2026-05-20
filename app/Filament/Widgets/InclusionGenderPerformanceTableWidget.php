<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ResolvesInclusionScope;
use App\Models\User;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Auth;

class InclusionGenderPerformanceTableWidget extends TableWidget
{
    use ResolvesInclusionScope;

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return static::canViewInclusionWidget();
    }

    public static function getSort(): int
    {
        $user = Auth::user();

        return ($user instanceof User && $user->isInstitution()) ? -29 : -78;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Average outcome by gender segment')
            ->description('Based on completed assessment attempts only. Percentages are score as a share of total marks.')
            ->records(fn (): array => $this->performanceRecords())
            ->columns([
                ColorColumn::make('color')
                    ->label('')
                    ->alignCenter()
                    ->width('3rem'),
                TextColumn::make('label')
                    ->label('Segment')
                    ->weight(FontWeight::Medium)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('average_percent')
                    ->label('Avg %')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 1).'%')
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('attempts')
                    ->label('Attempts')
                    ->numeric()
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('learners')
                    ->label('Learners')
                    ->numeric()
                    ->alignEnd()
                    ->sortable(),
            ])
            ->defaultSort('average_percent', 'desc')
            ->paginated([10, 25, 50])
            ->striped()
            ->emptyStateHeading('No completed attempts yet')
            ->emptyStateDescription('Performance by gender appears once learners complete marked assessments.');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function performanceRecords(): array
    {
        return collect($this->inclusionViewData()['performance_rows'] ?? [])
            ->map(fn (array $row): array => [
                'key' => (string) $row['key'],
                'label' => $row['label'],
                'average_percent' => $row['average_percent'],
                'attempts' => $row['attempts'],
                'learners' => $row['learners'],
                'color' => $row['color'],
            ])
            ->values()
            ->all();
    }
}
