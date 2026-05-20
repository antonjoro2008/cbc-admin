<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\SchoolAnalytics\SchoolActionItemsWidget;
use App\Filament\Widgets\SchoolAnalytics\SchoolAssessmentUsageTableWidget;
use App\Filament\Widgets\SchoolAnalytics\SchoolClassroomsTableWidget;
use App\Filament\Widgets\SchoolAnalytics\SchoolCompetencyAreasTableWidget;
use App\Filament\Widgets\SchoolAnalytics\SchoolGenderOutcomesTableWidget;
use App\Filament\Widgets\SchoolAnalytics\SchoolGenderRosterTableWidget;
use App\Filament\Widgets\SchoolAnalytics\SchoolInclusionStatsWidget;
use App\Filament\Widgets\SchoolAnalytics\SchoolLearnersTableWidget;
use App\Filament\Widgets\SchoolAnalytics\SchoolOverviewStatsWidget;
use App\Models\Institution;
use App\Models\User;
use App\Services\DashboardAnalyticsService;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;
use Illuminate\Contracts\Support\Htmlable;

class SchoolAnalyticsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?string $navigationLabel = 'School analytics';

    protected static ?string $title = 'School analytics';

    protected static ?int $navigationSort = 2;

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

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('institutionId')
                    ->label('School / institution')
                    ->options(fn (): array => Institution::orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->live()
                    ->native(false)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getWidgetData(): array
    {
        return [
            'institutionId' => $this->institutionId,
        ];
    }

    /**
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        if (! $this->institutionId) {
            return [];
        }

        $analytics = app(DashboardAnalyticsService::class)->institutionAnalyticsById($this->institutionId);
        $learnerCount = (int) ($analytics['summary']['learners'] ?? 0);

        if ($learnerCount === 0) {
            return [];
        }

        $widgets = [
            SchoolOverviewStatsWidget::class,
            SchoolLearnersTableWidget::class,
            SchoolInclusionStatsWidget::class,
            SchoolGenderRosterTableWidget::class,
            SchoolGenderOutcomesTableWidget::class,
        ];

        if (! empty($analytics['classroom_breakdown'])) {
            $widgets[] = SchoolClassroomsTableWidget::class;
        }

        if (! empty($analytics['category_breakdown'])) {
            $widgets[] = SchoolCompetencyAreasTableWidget::class;
        }

        if (! empty($analytics['assessment_usage'])) {
            $widgets[] = SchoolAssessmentUsageTableWidget::class;
        }

        if (! empty($analytics['action_items'])) {
            $widgets[] = SchoolActionItemsWidget::class;
        }

        return $widgets;
    }

    /**
     * @return int | array<string, ?int>
     */
    public function getColumns(): int | array
    {
        return [
            'default' => 1,
            'lg' => 2,
        ];
    }

    public function getTitle(): string | Htmlable
    {
        if (! $this->institutionId) {
            return static::$title ?? 'School analytics';
        }

        $name = Institution::query()->whereKey($this->institutionId)->value('name');

        return $name ?: 'School analytics';
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFiltersFormContentComponent(),
                $this->getWidgetsContentComponent(),
            ]);
    }

    public function getFiltersFormContentComponent(): Component
    {
        return EmbeddedSchema::make('filtersForm');
    }

    public function getWidgetsContentComponent(): Component
    {
        $widgets = $this->getWidgetsSchemaComponents($this->getWidgets());

        return Grid::make($this->getColumns())
            ->schema($widgets)
            ->hidden(empty($widgets));
    }
}
