@echo off
echo Rolling back AttemptAnswers resource changes...

REM Rollback AttemptAnswer resource
echo Rolling back AttemptAnswer resource...
Rename-Item "app\Filament\Resources\AttemptAnswers\AttemptAnswerResource.php.disabled" "AttemptAnswerResource.php"

REM Rollback AttemptAnswer pages
echo Rolling back AttemptAnswer pages...
Get-ChildItem "app\Filament\Resources\AttemptAnswers\Pages\*.php.disabled" | ForEach-Object { Rename-Item $_.FullName $_.Name.Replace('.php.disabled', '.php') }

REM Remove relation manager from Assessment resource
echo Removing relation manager from Assessment resource...
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\AssessmentResource.php') -replace 'use App\\Filament\\Resources\\Assessments\\RelationManagers\\AttemptAnswersRelationManager;', '' | Set-Content 'app\Filament\Resources\Assessments\AssessmentResource.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\AssessmentResource.php') -replace '            AttemptAnswersRelationManager::class,', '' | Set-Content 'app\Filament\Resources\Assessments\AssessmentResource.php'"

REM Remove attemptAnswers relationship from Assessment model
echo Removing attemptAnswers relationship from Assessment model...
powershell -Command "(Get-Content 'app\Models\Assessment.php') -replace '    /\*\*', '    // /**' | Set-Content 'app\Models\Assessment.php'"
powershell -Command "(Get-Content 'app\Models\Assessment.php') -replace '     \* Get the attempt answers for the assessment through attempts\.', '     // * Get the attempt answers for the assessment through attempts.' | Set-Content 'app\Models\Assessment.php'"
powershell -Command "(Get-Content 'app\Models\Assessment.php') -replace '     \*/', '     // */' | Set-Content 'app\Models\Assessment.php'"
powershell -Command "(Get-Content 'app\Models\Assessment.php') -replace '    public function attemptAnswers\(\)', '    // public function attemptAnswers()' | Set-Content 'app\Models\Assessment.php'"
powershell -Command "(Get-Content 'app\Models\Assessment.php') -replace '    \{', '    // {' | Set-Content 'app\Models\Assessment.php'"
powershell -Command "(Get-Content 'app\Models\Assessment.php') -replace '        return \$this->hasManyThrough\(', '        // return $this->hasManyThrough(' | Set-Content 'app\Models\Assessment.php'"
powershell -Command "(Get-Content 'app\Models\Assessment.php') -replace '            AttemptAnswer::class,', '            // AttemptAnswer::class,' | Set-Content 'app\Models\Assessment.php'"
powershell -Command "(Get-Content 'app\Models\Assessment.php') -replace '            AssessmentAttempt::class,', '            // AssessmentAttempt::class,' | Set-Content 'app\Models\Assessment.php'"
powershell -Command "(Get-Content 'app\Models\Assessment.php') -replace '            ''assessment_id'', // Foreign key on assessment_attempts table', '            // ''assessment_id'', // Foreign key on assessment_attempts table' | Set-Content 'app\Models\Assessment.php'"
powershell -Command "(Get-Content 'app\Models\Assessment.php') -replace '            ''attempt_id'', // Foreign key on attempt_answers table', '            // ''attempt_id'', // Foreign key on attempt_answers table' | Set-Content 'app\Models\Assessment.php'"
powershell -Command "(Get-Content 'app\Models\Assessment.php') -replace '            ''id'', // Local key on assessments table', '            // ''id'', // Local key on assessments table' | Set-Content 'app\Models\Assessment.php'"
powershell -Command "(Get-Content 'app\Models\Assessment.php') -replace '            ''id'' // Local key on assessment_attempts table', '            // ''id'' // Local key on assessment_attempts table' | Set-Content 'app\Models\Assessment.php'"
powershell -Command "(Get-Content 'app\Models\Assessment.php') -replace '        \);', '        // );' | Set-Content 'app\Models\Assessment.php'"
powershell -Command "(Get-Content 'app\Models\Assessment.php') -replace '    \}', '    // }' | Set-Content 'app\Models\Assessment.php'"

REM Remove Answers action from AttemptsRelationManager
echo Removing Answers action from AttemptsRelationManager...
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php') -replace 'use Filament\\Actions\\Action;', '' | Set-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php') -replace 'use Filament\\Infolists\\Components\\TextEntry;', '' | Set-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php') -replace 'use Filament\\Infolists\\Components\\RepeatableEntry;', '' | Set-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php') -replace 'use Filament\\Infolists\\Components\\IconEntry;', '' | Set-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php'"

REM Delete relation manager file
echo Deleting relation manager file...
Remove-Item "app\Filament\Resources\Assessments\RelationManagers\AttemptAnswersRelationManager.php" -ErrorAction SilentlyContinue

echo Rollback completed!
pause
