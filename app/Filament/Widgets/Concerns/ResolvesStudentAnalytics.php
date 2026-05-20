<?php

namespace App\Filament\Widgets\Concerns;

use App\Services\DashboardAnalyticsService;
use Livewire\Attributes\Reactive;

trait ResolvesStudentAnalytics
{
    public static function isDiscovered(): bool
    {
        return false;
    }

    #[Reactive]
    public ?int $studentId = null;

    /**
     * @return array<string, mixed>
     */
    protected function studentAnalytics(): array
    {
        $id = $this->studentId;

        if (! $id) {
            return [];
        }

        return app(DashboardAnalyticsService::class)->studentAnalyticsById($id);
    }

    public static function canView(): bool
    {
        return true;
    }
}
