<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Institution;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->required(),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required(),
                    ]),
                Grid::make(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('phone_number')
                            ->label('Phone number')
                            ->tel(),
                        Select::make('user_type')
                            ->label('User type')
                            ->options([
                                'admin' => 'Admin',
                                'institution' => 'Institution',
                                'teacher' => 'Teacher',
                                'student' => 'Student',
                                'parent' => 'Parent',
                            ])
                            ->required(),
                    ]),
                Grid::make(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('institution_id')
                            ->label('Institution')
                            ->options(fn (): array => Institution::orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->nullable(),
                        TextInput::make('admission_number')
                            ->label('Admission number'),
                    ]),
                Grid::make(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create'),
                        TextInput::make('password_confirmation')
                            ->label('Confirm password')
                            ->password()
                            ->dehydrated(false)
                            ->required(fn (string $operation): bool => $operation === 'create'),
                    ]),
                Toggle::make('is_active')
                    ->columnSpanFull()
                    ->label('Active')
                    ->default(true),
            ]);
    }
}
