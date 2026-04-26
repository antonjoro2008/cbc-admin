<?php

namespace App\Filament\Widgets;

use App\Models\AssessmentAttempt;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class InstitutionRecentAttemptsWidget extends TableWidget
{
    protected static ?int $sort = -32;

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->isInstitution() && (bool) $user->institution_id;
    }

    public function table(Table $table): Table
    {
        $institutionId = (int) Auth::user()->institution_id;

        return $table
            ->heading('Recent completed assessments')
            ->description('A live register of the latest finished attempts by learners at your institution. Scores are shown alongside percentage of total marks (CBE-style view).')
            ->query(
                AssessmentAttempt::query()
                    ->whereHas('student', fn (Builder $q) => $q
                        ->where('institution_id', $institutionId)
                        ->where('user_type', 'student'))
                    ->whereNotNull('completed_at')
                    ->with(['student', 'assessment'])
                    ->orderByDesc('completed_at')
            )
            ->columns([
                TextColumn::make('student.name')
                    ->label('Learner')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('student.grade_level')
                    ->label('Grade')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('assessment.title')
                    ->label('Assessment')
                    ->limit(45)
                    ->searchable()
                    ->tooltip(function (AssessmentAttempt $record): ?string {
                        $t = $record->assessment?->title;

                        return $t && strlen($t) > 45 ? $t : null;
                    }),
                TextColumn::make('completed_at')
                    ->label('Completed')
                    ->dateTime('M j, Y g:i a')
                    ->sortable(),
                TextColumn::make('score')
                    ->label('Marks')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('result_percent')
                    ->label('Result %')
                    ->getStateUsing(function (AssessmentAttempt $record): string {
                        $outOf = $record->assessment?->questions()->sum('marks');
                        if (! $outOf || $record->score === null) {
                            return '—';
                        }

                        return number_format((float) (($record->score / $outOf) * 100), 1).'%';
                    }),
            ])
            ->defaultSort('completed_at', 'desc')
            ->paginated([10, 25, 50]);
    }
}
