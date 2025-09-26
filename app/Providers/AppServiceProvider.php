<?php

namespace App\Providers;

use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Support\ServiceProvider;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Component;
use Filament\Infolists\Components\TextEntry;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Table::configureUsing(function (Table $table): void {
            $table->defaultSort('id', 'DESC')
                ->paginated([10, 25, 50, 100, 200, 500]);
        });

        TextEntry::configureUsing(function (TextEntry $textEntry) {
            $textEntry->color('success');
        });

        DatePicker::configureUsing(function (DatePicker $component) {
            $component->displayFormat('d/m/Y');
        });

        Component::configureUsing(function (Component $component) {
            if ($label = $component->getLabel()) {
                $component->label(Str::title($label));
            }
        });
    }
}