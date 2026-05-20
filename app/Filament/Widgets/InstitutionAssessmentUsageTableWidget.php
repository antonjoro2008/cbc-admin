<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Services\DashboardAnalyticsService;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Auth;

class InstitutionAssessmentUsageTableWidget extends TableWidget
{
    protected static ?int $sort = -28;

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->isInstitution() && (bool) $user->institution_id;
    }

    public function table(Table $table): Table
    {
        $rows = app(DashboardAnalyticsService::class)
            ->institutionAnalytics(Auth::user())['assessment_usage'] ?? [];

        return $table
            ->heading('Assessment usage at your school')
            ->description('Attempts and average scores per assessment used by your learners.')
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
                    ->label('Total attempts')
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
            ->emptyStateHeading('No assessment usage yet')
            ->emptyStateDescription('Usage appears once learners start and complete assessments.');
    }
}
