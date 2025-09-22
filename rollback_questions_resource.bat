@echo off
echo Restoring Questions Resource from disabled state...

REM Restore main resource file
move "app\Filament\Resources\Questions\QuestionResource.php.disabled" "app\Filament\Resources\Questions\QuestionResource.php"

REM Restore page files
move "app\Filament\Resources\Questions\Pages\CreateQuestion.php.disabled" "app\Filament\Resources\Questions\Pages\CreateQuestion.php"
move "app\Filament\Resources\Questions\Pages\EditQuestion.php.disabled" "app\Filament\Resources\Questions\Pages\EditQuestion.php"
move "app\Filament\Resources\Questions\Pages\ListQuestions.php.disabled" "app\Filament\Resources\Questions\Pages\ListQuestions.php"
move "app\Filament\Resources\Questions\Pages\ViewQuestion.php.disabled" "app\Filament\Resources\Questions\Pages\ViewQuestion.php"

REM Restore relation manager
move "app\Filament\Resources\Questions\RelationManagers\ChildQuestionsRelationManager.php.disabled" "app\Filament\Resources\Questions\RelationManagers\ChildQuestionsRelationManager.php"

echo Questions Resource restored successfully!
echo Run: php artisan config:clear
echo Run: php artisan route:clear
echo Run: php artisan view:clear
pause
