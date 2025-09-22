@echo off
echo Restoring all Question-related Resources from disabled state...

REM Restore Questions Resource
move "app\Filament\Resources\Questions\QuestionResource.php.disabled" "app\Filament\Resources\Questions\QuestionResource.php"
move "app\Filament\Resources\Questions\Pages\CreateQuestion.php.disabled" "app\Filament\Resources\Questions\Pages\CreateQuestion.php"
move "app\Filament\Resources\Questions\Pages\EditQuestion.php.disabled" "app\Filament\Resources\Questions\Pages\EditQuestion.php"
move "app\Filament\Resources\Questions\Pages\ListQuestions.php.disabled" "app\Filament\Resources\Questions\Pages\ListQuestions.php"
move "app\Filament\Resources\Questions\Pages\ViewQuestion.php.disabled" "app\Filament\Resources\Questions\Pages\ViewQuestion.php"
move "app\Filament\Resources\Questions\RelationManagers\ChildQuestionsRelationManager.php.disabled" "app\Filament\Resources\Questions\RelationManagers\ChildQuestionsRelationManager.php"

REM Restore Answers Resource
move "app\Filament\Resources\Answers\AnswerResource.php.disabled" "app\Filament\Resources\Answers\AnswerResource.php"
move "app\Filament\Resources\Answers\Pages\CreateAnswer.php.disabled" "app\Filament\Resources\Answers\Pages\CreateAnswer.php"
move "app\Filament\Resources\Answers\Pages\EditAnswer.php.disabled" "app\Filament\Resources\Answers\Pages\EditAnswer.php"
move "app\Filament\Resources\Answers\Pages\ListAnswers.php.disabled" "app\Filament\Resources\Answers\Pages\ListAnswers.php"
move "app\Filament\Resources\Answers\Pages\ViewAnswer.php.disabled" "app\Filament\Resources\Answers\Pages\ViewAnswer.php"

REM Restore AnswerMedia Resource
move "app\Filament\Resources\AnswerMedia\AnswerMediaResource.php.disabled" "app\Filament\Resources\AnswerMedia\AnswerMediaResource.php"
move "app\Filament\Resources\AnswerMedia\Pages\CreateAnswerMedia.php.disabled" "app\Filament\Resources\AnswerMedia\Pages\CreateAnswerMedia.php"
move "app\Filament\Resources\AnswerMedia\Pages\EditAnswerMedia.php.disabled" "app\Filament\Resources\AnswerMedia\Pages\EditAnswerMedia.php"
move "app\Filament\Resources\AnswerMedia\Pages\ListAnswerMedia.php.disabled" "app\Filament\Resources\AnswerMedia\Pages\ListAnswerMedia.php"
move "app\Filament\Resources\AnswerMedia\Pages\ViewAnswerMedia.php.disabled" "app\Filament\Resources\AnswerMedia\Pages\ViewAnswerMedia.php"

REM Restore QuestionMedia Resource
move "app\Filament\Resources\QuestionMedia\QuestionMediaResource.php.disabled" "app\Filament\Resources\QuestionMedia\QuestionMediaResource.php"
move "app\Filament\Resources\QuestionMedia\Pages\CreateQuestionMedia.php.disabled" "app\Filament\Resources\QuestionMedia\Pages\CreateQuestionMedia.php"
move "app\Filament\Resources\QuestionMedia\Pages\EditQuestionMedia.php.disabled" "app\Filament\Resources\QuestionMedia\Pages\EditQuestionMedia.php"
move "app\Filament\Resources\QuestionMedia\Pages\ListQuestionMedia.php.disabled" "app\Filament\Resources\QuestionMedia\Pages\ListQuestionMedia.php"
move "app\Filament\Resources\QuestionMedia\Pages\ViewQuestionMedia.php.disabled" "app\Filament\Resources\QuestionMedia\Pages\ViewQuestionMedia.php"

echo All Question-related Resources restored successfully!
echo Run: php artisan config:clear
echo Run: php artisan route:clear
echo Run: php artisan view:clear
pause
