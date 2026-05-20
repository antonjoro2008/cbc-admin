<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ResolvesInclusionScope;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class InclusionCoverageStatsWidget extends StatsOverviewWidget
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

        return ($user instanceof User && $user->isInstitution()) ? -31 : -80;
    }

    protected function getHeading(): ?string
    {
        return $this->inclusionScope()['scope'] === 'platform'
            ? 'Platform-wide inclusion & gender equity'
            : 'Inclusion & gender equity';
    }

    protected function getDescription(): ?string
    {
        return $this->inclusionScope()['scope'] === 'platform'
            ? 'Reporting coverage across all students and schools on Gravity CBC.'
            : 'Gender reporting coverage and roster readiness for your institution.';
    }

    protected function getStats(): array
    {
        $gr = $this->inclusionViewData()['gender_reporting'] ?? [];

        return [
            Stat::make('Gender reporting rate', number_format($gr['reporting_rate_percent'] ?? 0, 1).'%')
                ->description(($gr['learners_with_gender'] ?? 0).' learners with gender recorded')
                ->descriptionIcon(Heroicon::OutlinedIdentification)
                ->color('info'),

            Stat::make('With gender recorded', (string) ($gr['learners_with_gender'] ?? 0))
                ->description('Learners eligible for gender-segmented reporting')
                ->descriptionIcon(Heroicon::OutlinedUserGroup)
                ->color('success'),

            Stat::make('Gender not recorded', (string) ($gr['learners_without_gender'] ?? 0))
                ->description('Collect gender where policy allows to improve equity analytics')
                ->descriptionIcon(Heroicon::OutlinedUserMinus)
                ->color('warning'),
        ];
    }
}
