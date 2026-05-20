<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\AdminOnlyWidget;
use App\Models\AssessmentAttempt;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PlatformRecentAttemptsWidget extends TableWidget
{
    use AdminOnlyWidget;

    protected static ?int $sort = -60;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent completed assessments — all students')
            ->description('Latest finished attempts across every school and individual learner on the platform.')
            ->query(
                AssessmentAttempt::query()
                    ->whereHas('student', fn ($q) => $q->where('user_type', 'student'))
                    ->whereNotNull('completed_at')
                    ->with(['student.institution', 'assessment.subject'])
                    ->orderByDesc('completed_at')
            )
            ->columns([
                TextColumn::make('student.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('student.institution.name')
                    ->label('School')
                    ->placeholder('Individual')
                    ->toggleable(),
                TextColumn::make('student.gender')
                    ->label('Gender')
                    ->formatStateUsing(fn (?string $state) => $state
                        ? ucfirst(str_replace('_', ' ', $state))
                        : '—')
                    ->toggleable(),
                TextColumn::make('assessment.title')
                    ->label('Assessment')
                    ->limit(40)
                    ->searchable(),
                TextColumn::make('assessment.subject.name')
                    ->label('Subject')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('completed_at')
                    ->label('Completed')
                    ->dateTime('M j, Y g:i a')
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
