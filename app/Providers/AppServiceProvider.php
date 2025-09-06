<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Forms\Components\DatePicker;
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
        TextEntry::configureUsing(function (TextEntry $textEntry) {
            $textEntry->color('success');
        });

        DatePicker::configureUsing(function (DatePicker $component) {
            $component->displayFormat('d/m/Y');
        });
    }
}