<?php

namespace App\Filament\Widgets;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Institution;
use App\Models\Payment;
use App\Models\Question;
use App\Models\User;
use App\Models\Wallet;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatisticsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            // User Statistics
            Stat::make('Total Users', User::count())
                ->description('All registered users')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Total Institutions', Institution::count())
                ->description('Educational institutions')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('info'),

            // Assessment Statistics
            Stat::make('Total Assessments', Assessment::count())
                ->description('Created assessments')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),

            Stat::make('Total Questions', Question::count())
                ->description('Assessment questions')
                ->descriptionIcon('heroicon-m-question-mark-circle')
                ->color('primary'),

            Stat::make('Assessment Attempts', AssessmentAttempt::count())
                ->description('Student attempts')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('success'),

            // Payment Statistics
            Stat::make('Total Payments', Payment::count())
                ->description('All payment transactions')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('success'),

            Stat::make('Successful Payments', Payment::where('status', 'successful')->count())
                ->description('Completed transactions')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Pending Payments', Payment::where('status', 'pending')->count())
                ->description('Awaiting confirmation')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Total Revenue', $this->getTotalRevenue())
                ->description('From successful payments')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            // Performance Statistics
            Stat::make('Average Score', $this->getAverageScore())
                ->description('Across all attempts')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),

            // Recent Activity
            Stat::make('Recent Assessments', Assessment::where('created_at', '>=', now()->subDays(30))->count())
                ->description('Last 30 days')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('warning'),

            Stat::make('Recent Attempts', AssessmentAttempt::where('created_at', '>=', now()->subDays(7))->count())
                ->description('Last 7 days')
                ->descriptionIcon('heroicon-m-fire')
                ->color('danger'),
        ];
    }

    /**
     * Get total revenue from successful payments
     */
    private function getTotalRevenue(): string
    {
        $total = Payment::where('status', 'successful')->sum('amount');
        return 'KES ' . number_format($total, 2);
    }

    /**
     * Get average score across all completed attempts
     */
    private function getAverageScore(): string
    {
        $averageScore = AssessmentAttempt::whereNotNull('score')
            ->avg('score');

        if ($averageScore) {
            return round($averageScore, 1) . '%';
        }

        return 'N/A';
    }
}