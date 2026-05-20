<?php

namespace App\Filament\Pages;

use App\Models\Institution;
use App\Models\User;
use App\Services\DashboardAnalyticsService;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

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

    public function institutionSelect(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('institutionId')
                    ->label('Select school / institution')
                    ->options(fn (): array => Institution::orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->live()
                    ->native(false)
                    ->helperText('View institution-level analytics for any registered school — same depth as an institution admin dashboard.')
                    ->columnSpanFull(),
            ]);
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
            ];
        }

        $analytics = $service->institutionAnalyticsById($this->institutionId);

        return [
            'analytics' => $analytics,
            'inclusionView' => $service->formatInclusionForView($analytics['inclusion_metrics'] ?? []),
        ];
    }
}
