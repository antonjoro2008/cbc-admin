<?php

namespace App\Filament\Widgets\Concerns;

/**
 * Defer heavy analytics widgets so the dashboard shell loads first.
 * Analytics are cached (5 min) so lazy requests stay fast.
 */
trait LazyAnalyticsWidget
{
    protected static bool $isLazy = true;
}
