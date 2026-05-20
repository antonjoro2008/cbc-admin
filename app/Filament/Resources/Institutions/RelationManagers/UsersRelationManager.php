<?php

namespace App\Filament\Resources\Institutions\RelationManagers;

use App\Filament\Resources\RelationManagers\Concerns\DisplaysRelationManagerAnalytics;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Support\InstitutionAnalyticsWidgets;
use App\Models\Institution;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class UsersRelationManager extends RelationManager
{
    use DisplaysRelationManagerAnalytics;

    protected static string $relationship = 'users';

    protected static ?string $relatedResource = UserResource::class;

    /**
     * @return list<class-string>
     */
    protected function relationAnalyticsWidgets(): array
    {
        $owner = $this->getOwnerRecord();

        if (! $owner instanceof Institution) {
            return [];
        }

        return InstitutionAnalyticsWidgets::forInstitution((int) $owner->getKey());
    }

    /**
     * @return array<string, mixed>
     */
    protected function relationAnalyticsWidgetData(): array
    {
        return [
            'institutionId' => (int) $this->getOwnerRecord()->getKey(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
