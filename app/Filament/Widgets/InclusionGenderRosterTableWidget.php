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

class InclusionGenderRosterTableWidget extends TableWidget
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

        return ($user instanceof User && $user->isInstitution()) ? -30 : -79;
    }

    public function table(Table $table): Table
    {
        $scope = $this->inclusionScope();

        return $table
            ->heading('Learners by recorded gender')
            ->description($scope['scope'] === 'platform'
                ? 'Roster counts for all students across the platform (categories with zero learners are hidden).'
                : 'Roster counts for learners at your institution.')
            ->records(fn (): array => $this->rosterRecords())
            ->columns([
                ColorColumn::make('color')
                    ->label('')
                    ->alignCenter()
                    ->width('3rem'),
                TextColumn::make('label')
                    ->label('Gender category')
                    ->weight(FontWeight::Medium)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('count')
                    ->label('Learners')
                    ->numeric()
                    ->alignEnd()
                    ->sortable(),
            ])
            ->defaultSort('count', 'desc')
            ->paginated(false)
            ->striped();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rosterRecords(): array
    {
        return collect($this->inclusionViewData()['cohort_rows'] ?? [])
            ->map(fn (array $row): array => [
                'key' => (string) $row['key'],
                'label' => $row['label'],
                'count' => $row['count'],
                'color' => $row['color'],
            ])
            ->values()
            ->all();
    }
}
