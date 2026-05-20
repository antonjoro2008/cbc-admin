<?php

namespace App\Filament\Widgets\Concerns;

use App\Services\DashboardAnalyticsService;
use Livewire\Attributes\Reactive;

trait ResolvesTeacherAnalytics
{
    public static function isDiscovered(): bool
    {
        return false;
    }

    #[Reactive]
    public ?int $teacherId = null;

    /**
     * @return array<string, mixed>
     */
    protected function teacherAnalytics(): array
    {
        $id = $this->teacherId;

        if (! $id) {
            return [];
        }

        return app(DashboardAnalyticsService::class)->teacherAnalyticsById($id);
    }

    public static function canView(): bool
    {
        return true;
    }
}
