@echo off
echo Rolling back feedback integration changes...

REM Restore FeedbackMedia resource
echo Restoring FeedbackMedia resource...
Rename-Item "app\Filament\Resources\FeedbackMedia\FeedbackMediaResource.php.disabled" "FeedbackMediaResource.php"

REM Restore FeedbackMedia pages
echo Restoring FeedbackMedia pages...
Get-ChildItem "app\Filament\Resources\FeedbackMedia\Pages\*.php.disabled" | ForEach-Object { Rename-Item $_.FullName $_.Name.Replace('.php.disabled', '.php') }

REM Restore Feedback resource (if it was re-enabled)
echo Restoring Feedback resource...
if exist "app\Filament\Resources\Feedbacks\FeedbackResource.php.disabled" (
    Rename-Item "app\Filament\Resources\Feedbacks\FeedbackResource.php.disabled" "FeedbackResource.php"
)

REM Restore Feedback pages (if they were re-enabled)
echo Restoring Feedback pages...
Get-ChildItem "app\Filament\Resources\Feedbacks\Pages\*.php.disabled" | ForEach-Object { Rename-Item $_.FullName $_.Name.Replace('.php.disabled', '.php') }

REM Remove feedback display from Answers action
echo Removing feedback display from Answers action...
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php') -replace '                                RepeatableEntry::make\(''feedback''\)', '// RepeatableEntry::make(''feedback'')' | Set-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php') -replace '                                    ->label\(''Feedback''\)', '// ->label(''Feedback'')' | Set-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php') -replace '                                    ->schema\(\[', '// ->schema([' | Set-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php') -replace '                                        TextEntry::make\(''feedback_text''\)', '// TextEntry::make(''feedback_text'')' | Set-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php') -replace '                                            ->label\(''Feedback Text''\)', '// ->label(''Feedback Text'')' | Set-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php') -replace '                                            ->html\(\),', '// ->html(),' | Set-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php') -replace '                                        IconEntry::make\(''ai_generated''\)', '// IconEntry::make(''ai_generated'')' | Set-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php') -replace '                                            ->label\(''AI Generated''\)', '// ->label(''AI Generated'')' | Set-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php') -replace '                                            ->boolean\(\),', '// ->boolean(),' | Set-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php') -replace '                                        RepeatableEntry::make\(''media''\)', '// RepeatableEntry::make(''media'')' | Set-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php') -replace '                                            ->label\(''Feedback Media''\)', '// ->label(''Feedback Media'')' | Set-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php') -replace '                                            ->schema\(\[', '// ->schema([' | Set-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php') -replace '                                                TextEntry::make\(''media_type''\)', '// TextEntry::make(''media_type'')' | Set-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php') -replace '                                                    ->label\(''Media Type''\),', '// ->label(''Media Type''),' | Set-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php') -replace '                                                TextEntry::make\(''media_url''\)', '// TextEntry::make(''media_url'')' | Set-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php') -replace '                                                    ->label\(''Media URL''\),', '// ->label(''Media URL''),' | Set-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php') -replace '                                            \]\),', '// ]),' | Set-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php') -replace '                                    \]\),', '// ]),' | Set-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php'"

REM Remove feedback loading from fillForm
echo Removing feedback loading from fillForm...
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php') -replace '                        \$attempt = \$record->load\(\[''attemptAnswers\.question'', ''attemptAnswers\.feedback\.media'', ''student''\]\);', '                        \$attempt = \$record->load([''attemptAnswers.question'', ''student'']);' | Set-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php') -replace '                                    ''feedback'' => \$answer->feedback->map\(function \(\$feedback\) \{', '// ''feedback'' => \$answer->feedback->map(function (\$feedback) {' | Set-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php') -replace '                                        return \[', '// return [' | Set-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php') -replace '                                            ''feedback_text'' => \$feedback->feedback_text,', '// ''feedback_text'' => \$feedback->feedback_text,' | Set-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php') -replace '                                            ''ai_generated'' => \$feedback->ai_generated,', '// ''ai_generated'' => \$feedback->ai_generated,' | Set-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php') -replace '                                            ''media'' => \$feedback->media->map\(function \(\$media\) \{', '// ''media'' => \$feedback->media->map(function (\$media) {' | Set-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php') -replace '                                                return \[', '// return [' | Set-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php') -replace '                                                    ''media_type'' => \$media->media_type,', '// ''media_type'' => \$media->media_type,' | Set-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php') -replace '                                                    ''media_url'' => \$media->media_url,', '// ''media_url'' => \$media->media_url,' | Set-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php') -replace '                                                \];', '// ];' | Set-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php') -replace '                                            \}\)->toArray\(\),', '// })->toArray(),' | Set-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php') -replace '                                        \];', '// ];' | Set-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php'"
powershell -Command "(Get-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php') -replace '                                    \}\)->toArray\(\),', '// })->toArray(),' | Set-Content 'app\Filament\Resources\Assessments\RelationManagers\AttemptsRelationManager.php'"

echo Rollback completed!
pause
