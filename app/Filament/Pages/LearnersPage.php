<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Analytics\PlatformLearnersTableWidget;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;

class LearnersPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $navigationLabel = 'Learners';

    protected static ?string $title = 'Learners';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isAdmin();
    }

    /**
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        return [
            PlatformLearnersTableWidget::class,
        ];
    }

    /**
     * @return int | array<string, ?int>
     */
    public function getColumns(): int | array
    {
        return 1;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getWidgetsContentComponent(),
            ]);
    }

    public function getWidgetsContentComponent(): Component
    {
        return Grid::make($this->getColumns())
            ->schema($this->getWidgetsSchemaComponents($this->getWidgets()));
    }
}
