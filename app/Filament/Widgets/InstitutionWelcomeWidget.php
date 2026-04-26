<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class InstitutionWelcomeWidget extends Widget
{
    protected static ?int $sort = -50;

    protected string $view = 'filament.widgets.institution-welcome';

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->isInstitution();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        /** @var User $user */
        $user = Auth::user();
        $user->loadMissing('institution');

        $name = $user->institution?->name;

        return [
            'institution_name' => $name,
            'welcome_heading' => $name ? 'Welcome, '.$name : 'Welcome',
            'has_institution_scope' => (bool) $user->institution_id,
        ];
    }
}
