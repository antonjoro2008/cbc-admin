<?php

namespace App\Filament\Resources\AssessmentSections\RelationManagers;

use App\Models\Assessment;
use App\Models\CategoryTag;
use App\Models\Question;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('question_number')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('question_type')
                    ->label('Type')
                    ->badge(),
                TextColumn::make('question_text')
                    ->label('Question')
                    ->limit(60)
                    ->html()
                    ->wrap(),
                TextColumn::make('marks')
                    ->label('Marks')
                    ->sortable(),
                TextColumn::make('category_tag')
                    ->label('Tag')
                    ->placeholder('—'),
                TextColumn::make('assessment.title')
                    ->label('Assessment')
                    ->toggleable(),
            ])
            ->defaultSort('question_number')
            ->headerActions([
                CreateAction::make()
                    ->label('Add question')
                    ->slideOver()
                    ->modalWidth('3xl')
                    ->form($this->questionFormSchema())
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['section_id'] = $this->getOwnerRecord()->getKey();

                        if (empty($data['assessment_id'])) {
                            $data['assessment_id'] = Question::query()
                                ->where('section_id', $this->getOwnerRecord()->getKey())
                                ->value('assessment_id');
                        }

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->slideOver()
                    ->modalWidth('3xl')
                    ->form($this->questionFormSchema()),
                DeleteAction::make(),
            ]);
    }

    /**
     * @return array<int, mixed>
     */
    protected function questionFormSchema(): array
    {
        $sectionId = $this->getOwnerRecord()->getKey();

        return [
            Grid::make(2)
                ->schema([
                    Select::make('assessment_id')
                        ->label('Assessment')
                        ->options(fn (): array => Assessment::orderBy('title')->pluck('title', 'id')->all())
                        ->searchable()
                        ->required()
                        ->default(fn (): ?int => Question::query()
                            ->where('section_id', $sectionId)
                            ->value('assessment_id')),
                    TextInput::make('question_number')
                        ->label('Question #')
                        ->numeric()
                        ->required(),
                    Select::make('question_type')
                        ->label('Type')
                        ->options([
                            'mcq' => 'Multiple choice',
                            'essay' => 'Essay',
                            'short_answer' => 'Short answer',
                            'true_false' => 'True / false',
                            'matching' => 'Matching',
                            'fill_blank' => 'Fill in the blank',
                        ])
                        ->required(),
                    TextInput::make('marks')
                        ->label('Marks')
                        ->numeric()
                        ->minValue(1)
                        ->required(),
                ]),
            TextInput::make('category_tag')
                ->label('Category tag')
                ->datalist(CategoryTag::pluck('tag')->all()),
            RichEditor::make('question_text')
                ->label('Question text')
                ->required()
                ->columnSpanFull(),
        ];
    }
}
