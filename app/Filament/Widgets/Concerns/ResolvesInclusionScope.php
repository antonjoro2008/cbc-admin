<?php

namespace App\Filament\Widgets\Concerns;

use App\Models\User;
use App\Services\DashboardAnalyticsService;
use Illuminate\Support\Facades\Auth;

trait ResolvesInclusionScope
{
    /**
     * @return array{scope: string, institution_id: int|null, heading_suffix: string}
     */
    protected function inclusionScope(): array
    {
        $user = Auth::user();

        if ($user instanceof User && $user->isAdmin()) {
            return [
                'scope' => 'platform',
                'institution_id' => null,
                'heading_suffix' => 'platform-wide',
            ];
        }

        return [
            'scope' => 'institution',
            'institution_id' => $user instanceof User ? (int) $user->institution_id : null,
            'heading_suffix' => 'your institution',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function inclusionMetricsRaw(): array
    {
        $service = app(DashboardAnalyticsService::class);
        $scope = $this->inclusionScope();

        if ($scope['scope'] === 'platform') {
            return $service->adminAnalytics()['inclusion_metrics'];
        }

        if (! $scope['institution_id']) {
            return $service->inclusionMetricsForScope(null);
        }

        return $service->inclusionMetricsForScope($scope['institution_id']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function inclusionViewData(bool $hideEmptyCohortRows = true): array
    {
        return app(DashboardAnalyticsService::class)->formatInclusionForView(
            $this->inclusionMetricsRaw(),
            $hideEmptyCohortRows,
        );
    }

    public static function canViewInclusionWidget(): bool
    {
        $user = Auth::user();

        if ($user instanceof User && $user->isAdmin()) {
            return true;
        }

        return $user instanceof User && $user->isInstitution() && (bool) $user->institution_id;
    }
}
