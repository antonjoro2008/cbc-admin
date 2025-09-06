<?php

namespace App\Filament\Resources\FeedbackMedia\Schemas;

use App\Models\Feedback;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;

class FeedbackMediaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        Select::make('feedback_id')
                            ->label('Feedback')
                            ->options(Feedback::all()->pluck('subject', 'id'))
                            ->searchable()
                            ->required(),
                        Select::make('media_type')
                            ->label('Media Type')
                            ->options([
                                'image' => 'Image',
                                'video' => 'Video',
                                'audio' => 'Audio',
                                'pdf' => 'PDF',
                                'doc' => 'Document',
                                'link' => 'Link',
                            ])
                            ->required(),
                    ]),
                FileUpload::make('media_url')
                    ->label('Media File')
                    ->required(),
            ]);
    }
}
