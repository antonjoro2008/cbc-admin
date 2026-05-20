<?php

namespace App\Filament\Widgets\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

trait AdminOnlyWidget
{
    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->isAdmin();
    }
}
