<?php

namespace App\Filament\Widgets\Concerns;

use App\Services\DashboardAnalyticsService;
use Livewire\Attributes\Reactive;

trait ResolvesSchoolAnalytics
{
    public static function isDiscovered(): bool
    {
        return false;
    }

    #[Reactive]
    public ?int $institutionId = null;

    protected function schoolInstitutionId(): ?int
    {
        return $this->institutionId;
    }

    /**
     * @return array<string, mixed>
     */
    protected function schoolAnalytics(): array
    {
        $id = $this->schoolInstitutionId();

        if (! $id) {
            return [];
        }

        return app(DashboardAnalyticsService::class)->institutionAnalyticsById($id);
    }

    /**
     * @return array<string, mixed>
     */
    protected function schoolInclusionView(): array
    {
        $metrics = $this->schoolAnalytics()['inclusion_metrics'] ?? [];

        return app(DashboardAnalyticsService::class)->formatInclusionForView($metrics);
    }

    public static function canView(): bool
    {
        return true;
    }
}
