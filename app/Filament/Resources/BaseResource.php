<?php

namespace App\Filament\Resources;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Resource;

abstract class BaseResource extends Resource
{
    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();

        if ($user instanceof User && $user->isInstitution()) {
            return false;
        }

        return parent::canViewAny();
    }
}
