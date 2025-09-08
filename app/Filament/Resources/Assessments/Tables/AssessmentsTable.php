<?php

namespace App\Filament\Resources\Assessments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;

class AssessmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->wrap()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subject.name')
                    ->label('Subject')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('grade_level')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('paper_code')
                    ->searchable(),
                TextColumn::make('year')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('exam_body')
                    ->searchable(),
                TextColumn::make('duration_minutes')
                    ->label('Duration (min)')
                    ->sortable(),
                ToggleColumn::make('uses_answer_sheet')
                    ->label('Answer Sheet'),
                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(function ($state) {
                        return $state == 1 ? 'Active' : 'Inactive';
                    })
                    ->badge()
                    ->color(function ($state) {
                        return $state == 1 ? 'success' : 'danger';
                    })
                    ->icon(function ($state) {
                        return $state == 1 ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle';
                    })
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}