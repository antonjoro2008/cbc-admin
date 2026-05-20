<?php

namespace App\Filament\Widgets\Concerns;

use App\Services\DashboardAnalyticsService;
use Livewire\Attributes\Reactive;

trait ResolvesSubjectAnalytics
{
    public static function isDiscovered(): bool
    {
        return false;
    }

    #[Reactive]
    public ?int $subjectId = null;

    /**
     * @return array<string, mixed>
     */
    protected function subjectAnalytics(): array
    {
        $id = $this->subjectId;

        if (! $id) {
            return [];
        }

        return app(DashboardAnalyticsService::class)->subjectAnalyticsById($id);
    }

    public static function canView(): bool
    {
        return true;
    }
}
