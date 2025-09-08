<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
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
                        TextInput::make('phone')
                            ->label('Phone'),
                        TextInput::make('mpesa_phone')
                            ->label('MPESA Phone'),
                        Select::make('role')
                            ->label('Role')
                            ->options([
                                'admin' => 'Admin',
                                'teacher' => 'Teacher',
                                'student' => 'Student',
                            ])
                            ->required(),
                    ]),
                Grid::make(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->required(),
                        TextInput::make('password_confirmation')
                            ->label('Confirm Password')
                            ->password()
                            ->required(),
                    ]),
                Toggle::make('is_active')
                    ->columnSpanFull()
                    ->label('Active')
                    ->default(true),
            ]);
    }
}