<?php

namespace App\Filament\Tables;

use App\Filament\Resources\Users\UserResource;
use App\Models\Institution;
use App\Models\User;
use App\Services\InstitutionLearnerAnalyticsService;
use Filament\Actions\Action;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PlatformLearnersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => static::baseQuery())
            ->columns([
                TextColumn::make('name')
                    ->label('Learner')
                    ->weight(FontWeight::Medium)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('institution.name')
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
                TextColumn::make('classroom.name')
                    ->label('Class')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('gender')
                    ->label('Gender')
                    ->formatStateUsing(fn (?string $state): string => InstitutionLearnerAnalyticsService::genderLabel(
                        ($state && in_array($state, User::GENDER_VALUES, true)) ? $state : 'unspecified',
                    ))
                    ->toggleable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('phone_number')
                    ->label('Phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('learner_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'On track' => 'success',
                        'Needs support' => 'danger',
                        'Inactive (30d)' => 'gray',
                        default => 'warning',
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('learner_status', $direction);
                    }),
                TextColumn::make('completed_attempts')
                    ->label('Attempts')
                    ->numeric()
                    ->alignEnd()
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('completed_attempts', $direction);
                    }),
                TextColumn::make('average_percent')
                    ->label('Avg %')
                    ->formatStateUsing(fn ($state): string => $state !== null
                        ? number_format((float) $state, 1).'%'
                        : '—')
                    ->color(fn ($state, User $record): ?string => $record->learner_status === 'Needs support' ? 'danger' : null)
                    ->alignEnd()
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('average_percent', $direction);
                    }),
                TextColumn::make('competency_level')
                    ->label('CBE')
                    ->state(function (User $record): string {
                        if ($record->average_percent === null) {
                            return '—';
                        }

                        return app(InstitutionLearnerAnalyticsService::class)
                            ->competencyDescriptor((float) $record->average_percent);
                    })
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('institution_id')
                    ->label('Institution')
                    ->options(fn (): array => Institution::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),
                SelectFilter::make('learner_status')
                    ->label('Status')
                    ->options([
                        'On track' => 'On track',
                        'Needs support' => 'Needs support',
                        'Inactive (30d)' => 'Inactive (30d)',
                        'No attempts yet' => 'No attempts yet',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => static::applyStatusFilter(
                        $query,
                        $data['value'] ?? null,
                    )),
            ])
            ->recordActions([
                Action::make('viewAnalytics')
                    ->label('View')
                    ->icon('heroicon-o-chart-bar')
                    ->url(fn (User $record): string => UserResource::getUrl('view', [
                        'record' => $record,
                    ])),
            ])
            ->defaultSort('average_percent', 'desc')
            ->paginated([10, 25, 50, 100])
            ->striped()
            ->emptyStateHeading('No learners yet')
            ->emptyStateDescription('Students appear here once they register on the platform.');
    }

    public static function baseQuery(): Builder
    {
        $since = Carbon::now()->subDays(30)->startOfDay();

        $assessmentMarks = DB::table('questions')
            ->selectRaw('assessment_id, COALESCE(SUM(marks), 0) as total_marks')
            ->groupBy('assessment_id');

        $attemptStats = DB::table('assessment_attempts as aa')
            ->joinSub($assessmentMarks, 'am', 'am.assessment_id', '=', 'aa.assessment_id')
            ->whereNotNull('aa.completed_at')
            ->whereNotNull('aa.score')
            ->where('am.total_marks', '>', 0)
            ->selectRaw('aa.student_id')
            ->selectRaw('COUNT(*) as completed_attempts')
            ->selectRaw('ROUND(AVG(aa.score / am.total_marks * 100), 2) as average_percent')
            ->groupBy('aa.student_id');

        return User::query()
            ->where('users.user_type', 'student')
            ->leftJoinSub($attemptStats, 'attempt_stats', 'attempt_stats.student_id', '=', 'users.id')
            ->select('users.*')
            ->selectRaw('COALESCE(attempt_stats.completed_attempts, 0) as completed_attempts')
            ->addSelect('attempt_stats.average_percent')
            ->selectRaw(
                'CASE
                    WHEN NOT EXISTS (
                        SELECT 1 FROM assessment_attempts recent
                        WHERE recent.student_id = users.id
                          AND recent.completed_at IS NOT NULL
                          AND recent.completed_at >= ?
                    ) THEN ?
                    WHEN COALESCE(attempt_stats.completed_attempts, 0) = 0 THEN ?
                    WHEN attempt_stats.average_percent < 50 THEN ?
                    ELSE ?
                END as learner_status',
                [$since, 'Inactive (30d)', 'No attempts yet', 'Needs support', 'On track'],
            )
            ->with(['institution:id,name', 'classroom:id,name']);
    }

    public static function applyStatusFilter(Builder $query, ?string $status): Builder
    {
        if (blank($status)) {
            return $query;
        }

        $since = Carbon::now()->subDays(30)->startOfDay();

        return match ($status) {
            'Inactive (30d)' => $query->whereNotExists(function ($sub) use ($since): void {
                $sub->selectRaw('1')
                    ->from('assessment_attempts as recent')
                    ->whereColumn('recent.student_id', 'users.id')
                    ->whereNotNull('recent.completed_at')
                    ->where('recent.completed_at', '>=', $since);
            }),
            'No attempts yet' => $query
                ->whereRaw('COALESCE(attempt_stats.completed_attempts, 0) = 0')
                ->whereExists(function ($sub) use ($since): void {
                    $sub->selectRaw('1')
                        ->from('assessment_attempts as recent')
                        ->whereColumn('recent.student_id', 'users.id')
                        ->whereNotNull('recent.completed_at')
                        ->where('recent.completed_at', '>=', $since);
                }),
            'Needs support' => $query
                ->where('attempt_stats.average_percent', '<', 50)
                ->whereExists(function ($sub) use ($since): void {
                    $sub->selectRaw('1')
                        ->from('assessment_attempts as recent')
                        ->whereColumn('recent.student_id', 'users.id')
                        ->whereNotNull('recent.completed_at')
                        ->where('recent.completed_at', '>=', $since);
                }),
            'On track' => $query
                ->where('attempt_stats.average_percent', '>=', 50)
                ->whereExists(function ($sub) use ($since): void {
                    $sub->selectRaw('1')
                        ->from('assessment_attempts as recent')
                        ->whereColumn('recent.student_id', 'users.id')
                        ->whereNotNull('recent.completed_at')
                        ->where('recent.completed_at', '>=', $since);
                }),
            default => $query,
        };
    }
}
