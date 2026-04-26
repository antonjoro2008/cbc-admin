<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Pages\Dashboard as FilamentDashboard;
use Illuminate\Contracts\Support\Htmlable;

class AppDashboard extends FilamentDashboard
{
    public function getTitle(): string | Htmlable
    {
        $user = auth()->user();

        if ($user instanceof User && $user->isInstitution()) {
            $name = $user->institution?->name ?? 'Your institution';

            return 'Overview — '.$name;
        }

        return parent::getTitle();
    }

    /**
     * @return int | array<string, ?int>
     */
    public function getColumns(): int | array
    {
        $user = auth()->user();

        if ($user instanceof User && $user->isInstitution()) {
            return 1;
        }

        return parent::getColumns();
    }
}
