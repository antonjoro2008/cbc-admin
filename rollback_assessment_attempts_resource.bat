@echo off
echo Restoring AssessmentAttempt Resource from disabled state...

REM Restore main resource file
move "app\Filament\Resources\AssessmentAttempts\AssessmentAttemptResource.php.disabled" "app\Filament\Resources\AssessmentAttempts\AssessmentAttemptResource.php"

REM Restore page files
move "app\Filament\Resources\AssessmentAttempts\Pages\CreateAssessmentAttempt.php.disabled" "app\Filament\Resources\AssessmentAttempts\Pages\CreateAssessmentAttempt.php"
move "app\Filament\Resources\AssessmentAttempts\Pages\EditAssessmentAttempt.php.disabled" "app\Filament\Resources\AssessmentAttempts\Pages\EditAssessmentAttempt.php"
move "app\Filament\Resources\AssessmentAttempts\Pages\ListAssessmentAttempts.php.disabled" "app\Filament\Resources\AssessmentAttempts\Pages\ListAssessmentAttempts.php"
move "app\Filament\Resources\AssessmentAttempts\Pages\ViewAssessmentAttempt.php.disabled" "app\Filament\Resources\AssessmentAttempts\Pages\ViewAssessmentAttempt.php"

REM Restore relation manager references
powershell -Command "(Get-Content 'app\Filament\Resources\Users\RelationManagers\AssessmentAttemptsRelationManager.php') -replace 'use Filament\\Actions\\CreateAction;', 'use App\\Filament\\Resources\\AssessmentAttempts\\AssessmentAttemptResource;`nuse Filament\\Actions\\CreateAction;' | Set-Content 'app\Filament\Resources\Users\RelationManagers\AssessmentAttemptsRelationManager.php'"

powershell -Command "(Get-Content 'app\Filament\Resources\Users\RelationManagers\AssessmentAttemptsRelationManager.php') -replace 'protected static string \$relationship = ''assessmentAttempts'';', 'protected static string $relationship = ''assessmentAttempts'';`n`n    protected static ?string $relatedResource = AssessmentAttemptResource::class;' | Set-Content 'app\Filament\Resources\Users\RelationManagers\AssessmentAttemptsRelationManager.php'"

powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php') -replace 'use Filament\\Actions\\CreateAction;', 'use App\\Filament\\Resources\\AssessmentAttempts\\AssessmentAttemptResource;`nuse Filament\\Actions\\CreateAction;' | Set-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php'"

powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php') -replace 'protected static string \$relationship = ''attempts'';', 'protected static string $relationship = ''attempts'';`n`n    protected static ?string $relatedResource = AssessmentAttemptResource::class;' | Set-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php'"

echo AssessmentAttempt Resource and relation manager references restored successfully!
echo Run: php artisan config:clear
echo Run: php artisan route:clear
echo Run: php artisan view:clear
pause
