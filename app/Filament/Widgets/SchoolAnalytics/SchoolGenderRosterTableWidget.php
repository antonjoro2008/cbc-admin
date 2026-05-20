<?php

namespace App\Filament\Widgets\SchoolAnalytics;

use App\Filament\Widgets\Concerns\ResolvesInstitutionAnalytics;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class SchoolGenderRosterTableWidget extends TableWidget
{
    use ResolvesInstitutionAnalytics;

    protected int | string | array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Roster by gender')
            ->description(null)
            ->records(fn (): array => collect($this->institutionInclusionView()['cohort_rows'] ?? [])
                ->map(fn (array $row): array => [
                    'key' => (string) $row['key'],
                    'label' => $row['label'],
                    'count' => $row['count'],
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
                TextColumn::make('count')
                    ->label('Learners')
                    ->numeric()
                    ->alignEnd(),
            ])
            ->paginated(false)
            ->striped()
            ->emptyStateHeading('No roster data');
    }
}
