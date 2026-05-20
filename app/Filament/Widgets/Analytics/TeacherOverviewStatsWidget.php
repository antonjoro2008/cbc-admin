<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Concerns\ResolvesTeacherAnalytics;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TeacherOverviewStatsWidget extends StatsOverviewWidget
{
    use ResolvesTeacherAnalytics;

    protected int | string | array $columnSpan = 'full';

    protected function getHeading(): ?string
    {
        $classroom = $this->teacherAnalytics()['classroom']['name'] ?? null;

        return $classroom ? 'Classroom — '.$classroom : 'Teacher overview';
    }

    protected function getDescription(): ?string
    {
        $message = $this->teacherAnalytics()['message'] ?? null;

        return $message;
    }

    protected function getStats(): array
    {
        $overview = $this->teacherAnalytics()['overview'] ?? [];

        if ($overview === []) {
            return [
                Stat::make('Classroom', 'Not assigned')
                    ->description('Link this teacher to a classroom to unlock analytics'),
            ];
        }

        return [
            Stat::make('Learners in class', (string) ($overview['learners_in_class'] ?? 0)),
            Stat::make('Class average', number_format((float) ($overview['class_average_score_percent'] ?? 0), 1).'%')
                ->description($this->teacherAnalytics()['insights']['average_level'] ?? '—'),
            Stat::make('Completed attempts', (string) ($overview['total_completed_attempts'] ?? 0)),
            Stat::make('Completion rate', ($overview['completion_rate_percent'] ?? 0).'%'),
        ];
    }
}
