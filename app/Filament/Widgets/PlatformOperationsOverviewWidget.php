<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\AdminOnlyWidget;
use App\Filament\Widgets\Concerns\LazyAnalyticsWidget;
use App\Services\DashboardAnalyticsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformOperationsOverviewWidget extends StatsOverviewWidget
{
    use AdminOnlyWidget;
    use LazyAnalyticsWidget;

    protected static ?int $sort = -94;

    protected int | string | array $columnSpan = 'full';

    protected function getHeading(): ?string
    {
        return 'System operations';
    }

    protected function getDescription(): ?string
    {
        return 'Tokens, M-PESA payments, assessments, and platform health.';
    }

    protected function getStats(): array
    {
        $overview = app(DashboardAnalyticsService::class)->adminAnalytics()['overview'];

        $systemOnline = ($overview['system_status'] ?? 'online') === 'online';

        return [
            Stat::make('Assessments created', (string) ($overview['total_assessments'] ?? 0))
                ->description('Published and draft assessments on the platform')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),

            Stat::make('Assessments completed', (string) ($overview['total_completed_attempts'] ?? 0))
                ->description(($overview['attempts_last_30_days'] ?? 0).' in the last 30 days')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Tokens purchased', number_format($overview['total_tokens_purchased'] ?? 0, 0))
                ->description('Credits added via purchases and wallet top-ups')
                ->descriptionIcon('heroicon-m-plus-circle')
                ->color('primary'),

            Stat::make('Tokens used', number_format($overview['total_tokens_used'] ?? 0, 0))
                ->description('Tokens consumed by assessment activity')
                ->descriptionIcon('heroicon-m-minus-circle')
                ->color('warning'),

            Stat::make('M-PESA successful', (string) ($overview['mpesa_payment_success_count'] ?? 0))
                ->description('Successful M-PESA payment transactions')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('M-PESA failed', (string) ($overview['mpesa_payment_failure_count'] ?? 0))
                ->description('Failed M-PESA payment attempts')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('System status', $overview['system_status_label'] ?? 'Online')
                ->description($systemOnline ? 'API and database reachable' : 'Check server connectivity')
                ->descriptionIcon($systemOnline ? 'heroicon-m-signal' : 'heroicon-m-exclamation-triangle')
                ->color($systemOnline ? 'success' : 'danger'),
        ];
    }
}
