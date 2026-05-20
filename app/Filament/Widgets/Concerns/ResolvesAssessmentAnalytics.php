<?php

namespace App\Filament\Widgets\Concerns;

use App\Services\DashboardAnalyticsService;
use Livewire\Attributes\Reactive;

trait ResolvesAssessmentAnalytics
{
    public static function isDiscovered(): bool
    {
        return false;
    }

    #[Reactive]
    public ?int $assessmentId = null;

    /**
     * @return array<string, mixed>
     */
    protected function assessmentAnalytics(): array
    {
        $id = $this->assessmentId;

        if (! $id) {
            return [];
        }

        return app(DashboardAnalyticsService::class)->assessmentAnalyticsById($id);
    }

    public static function canView(): bool
    {
        return true;
    }
}
