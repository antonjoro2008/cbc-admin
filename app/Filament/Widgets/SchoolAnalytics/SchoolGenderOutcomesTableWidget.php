<?php

namespace App\Filament\Widgets\SchoolAnalytics;

use App\Filament\Widgets\Concerns\ResolvesSchoolAnalytics;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class SchoolGenderOutcomesTableWidget extends TableWidget
{
    use ResolvesSchoolAnalytics;

    protected int | string | array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Outcomes by gender')
            ->description(null)
            ->records(fn (): array => collect($this->schoolInclusionView()['performance_rows'] ?? [])
                ->map(fn (array $row): array => [
                    'key' => (string) $row['key'],
                    'label' => $row['label'],
                    'average_percent' => $row['average_percent'],
                    'attempts' => $row['attempts'],
                    'learners' => $row['learners'],
                    'color' => $row['color'],
                ])
                ->values()
                ->all())
            ->columns([
                ColorColumn::make('color')
                    ->label('')
                    ->alignCenter()
                    ->width('3rem'),
                TextColumn::make('label')
                    ->label('Segment')
                    ->weight(FontWeight::Medium),
                TextColumn::make('average_percent')
                    ->label('Avg %')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 1).'%')
                    ->alignEnd(),
                TextColumn::make('attempts')
                    ->label('Attempts')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('learners')
                    ->label('Learners')
                    ->numeric()
                    ->alignEnd(),
            ])
            ->paginated(false)
            ->striped()
            ->emptyStateHeading('No outcome data');
    }
}
