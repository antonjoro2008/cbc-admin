<?php

namespace App\Filament\Widgets\Concerns;

use App\Services\DashboardAnalyticsService;
use Livewire\Attributes\Reactive;

trait ResolvesInstitutionAnalytics
{
    public static function isDiscovered(): bool
    {
        return false;
    }

    #[Reactive]
    public ?int $institutionId = null;

    protected function institutionAnalytics(): array
    {
        $id = $this->institutionId;

        if (! $id) {
            return [];
        }

        return app(DashboardAnalyticsService::class)->institutionAnalyticsById($id);
    }

    /**
     * @return array<string, mixed>
     */
    protected function institutionInclusionView(): array
    {
        $metrics = $this->institutionAnalytics()['inclusion_metrics'] ?? [];

        return app(DashboardAnalyticsService::class)->formatInclusionForView($metrics);
    }

    public static function canView(): bool
    {
        return true;
    }
}
