@echo off
echo Rolling back Feedback and FeedbackMedia resources...

REM Rollback Feedback resource
echo Rolling back Feedback resource...
Rename-Item "app\Filament\Resources\Feedbacks\FeedbackResource.php.disabled" "FeedbackResource.php"

REM Rollback Feedback pages
echo Rolling back Feedback pages...
Get-ChildItem "app\Filament\Resources\Feedbacks\Pages\*.php.disabled" | ForEach-Object { Rename-Item $_.FullName $_.Name.Replace('.php.disabled', '.php') }

REM Rollback FeedbackMedia resource
echo Rolling back FeedbackMedia resource...
Rename-Item "app\Filament\Resources\FeedbackMedia\FeedbackMediaResource.php.disabled" "FeedbackMediaResource.php"

REM Rollback FeedbackMedia pages
echo Rolling back FeedbackMedia pages...
Get-ChildItem "app\Filament\Resources\FeedbackMedia\Pages\*.php.disabled" | ForEach-Object { Rename-Item $_.FullName $_.Name.Replace('.php.disabled', '.php') }

REM Remove relation managers from Assessment resource
echo Removing relation managers from Assessment resource...
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\AssessmentResource.php') -replace 'use App\\Filament\\Resources\\Assessments\\RelationManagers\\FeedbacksRelationManager;', '' | Set-Content 'app\Filament\Resources\Assessments\AssessmentResource.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\AssessmentResource.php') -replace 'use App\\Filament\\Resources\\Assessments\\RelationManagers\\FeedbackMediaRelationManager;', '' | Set-Content 'app\Filament\Resources\Assessments\AssessmentResource.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\AssessmentResource.php') -replace '            FeedbacksRelationManager::class,', '' | Set-Content 'app\Filament\Resources\Assessments\AssessmentResource.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\AssessmentResource.php') -replace '            FeedbackMediaRelationManager::class,', '' | Set-Content 'app\Filament\Resources\Assessments\AssessmentResource.php'"

REM Delete relation manager files
echo Deleting relation manager files...
Remove-Item "app\Filament\Resources\Assessments\RelationManagers\FeedbacksRelationManager.php" -ErrorAction SilentlyContinue
Remove-Item "app\Filament\Resources\Assessments\RelationManagers\FeedbackMediaRelationManager.php" -ErrorAction SilentlyContinue

REM Restore references in other relation managers
echo Restoring references in other relation managers...
powershell -Command "(Get-Content 'app\Filament\Resources\AttemptAnswers\RelationManagers\FeedbackRelationManager.php') -replace 'use Filament\\Actions\\CreateAction;', 'use App\\Filament\\Resources\\Feedbacks\\FeedbackResource;`nuse Filament\\Actions\\CreateAction;' | Set-Content 'app\Filament\Resources\AttemptAnswers\RelationManagers\FeedbackRelationManager.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\AttemptAnswers\RelationManagers\FeedbackRelationManager.php') -replace '    protected static string `$relationship = ''feedback'';', '    protected static string `$relationship = ''feedback'';`n`n    protected static ?string `$relatedResource = FeedbackResource::class;' | Set-Content 'app\Filament\Resources\AttemptAnswers\RelationManagers\FeedbackRelationManager.php'"

powershell -Command "(Get-Content 'app\Filament\Resources\Feedback\RelationManagers\FeedbackMediaRelationManager.php') -replace 'use Filament\\Actions\\CreateAction;', 'use App\\Filament\\Resources\\FeedbackMedia\\FeedbackMediaResource;`nuse Filament\\Actions\\CreateAction;' | Set-Content 'app\Filament\Resources\Feedback\RelationManagers\FeedbackMediaRelationManager.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\Feedback\RelationManagers\FeedbackMediaRelationManager.php') -replace '    protected static string `$relationship = ''media'';', '    protected static string `$relationship = ''media'';`n`n    protected static ?string `$relatedResource = FeedbackMediaResource::class;' | Set-Content 'app\Filament\Resources\Feedback\RelationManagers\FeedbackMediaRelationManager.php'"

echo Rollback completed!
pause
