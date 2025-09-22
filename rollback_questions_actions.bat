@echo off
echo Restoring QuestionsRelationManager from backup...

REM Restore the backup file
copy "app\Filament\Resources\Assessments\RelationManagers\QuestionsRelationManager.php.backup" "app\Filament\Resources\Assessments\RelationManagers\QuestionsRelationManager.php"

echo QuestionsRelationManager restored successfully!
echo Run: php artisan config:clear
echo Run: php artisan route:clear
echo Run: php artisan view:clear
pause
