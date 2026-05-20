<?php

namespace App\Filament\Pages;

use App\Models\Institution;
use App\Models\User;
use App\Services\DashboardAnalyticsService;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use BackedEnum;

class SchoolAnalyticsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?string $navigationLabel = 'School analytics';

    protected static ?string $title = 'School analytics';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.school-analytics';

    public ?int $institutionId = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isAdmin();
    }

    public function mount(): void
    {
        $this->institutionId = Institution::query()->orderBy('name')->value('id');
    }

    public function updatedInstitutionId(): void
    {
        // Livewire refresh — view data recomputes on re-render.
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $service = app(DashboardAnalyticsService::class);

        if (! $this->institutionId) {
            return [
                'analytics' => [],
                'inclusionView' => [],
                'institutions' => Institution::orderBy('name')->pluck('name', 'id'),
            ];
        }

        $analytics = $service->institutionAnalyticsById($this->institutionId);

        return [
            'analytics' => $analytics,
            'inclusionView' => $service->formatInclusionForView($analytics['inclusion_metrics'] ?? []),
            'institutions' => Institution::orderBy('name')->pluck('name', 'id'),
        ];
    }
}
