<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Tables\PlatformLearnersTable;
use App\Filament\Widgets\Concerns\AdminOnlyWidget;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PlatformLearnersTableWidget extends TableWidget
{
    use AdminOnlyWidget;

    protected int | string | array $columnSpan = 'full';

    public static function isDiscovered(): bool
    {
        return false;
    }

    protected function makeTable(): Table
    {
        return $this->makeBaseTable()
            ->paginationMode(PaginationMode::Default);
    }

    public function table(Table $table): Table
    {
        return PlatformLearnersTable::configure(
            $table->heading(null)->description(null),
        );
    }
}
