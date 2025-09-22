@echo off
echo Restoring AssessmentSection navigation labels...

REM Restore the original navigation labels
powershell -Command "(Get-Content 'app\Filament\Resources\AssessmentSections\AssessmentSectionResource.php') -replace 'protected static \?string \$navigationLabel = ''Sections'';', 'protected static ?string $navigationLabel = ''Assessment Sections'';' | Set-Content 'app\Filament\Resources\AssessmentSections\AssessmentSectionResource.php'"

powershell -Command "(Get-Content 'app\Filament\Resources\AssessmentSections\AssessmentSectionResource.php') -replace 'protected static \?string \$modelLabel = ''Section'';', 'protected static ?string $modelLabel = ''Assessment Section'';' | Set-Content 'app\Filament\Resources\AssessmentSections\AssessmentSectionResource.php'"

powershell -Command "(Get-Content 'app\Filament\Resources\AssessmentSections\AssessmentSectionResource.php') -replace 'protected static \?string \$pluralModelLabel = ''Sections'';', 'protected static ?string $pluralModelLabel = ''Assessment Sections'';' | Set-Content 'app\Filament\Resources\AssessmentSections\AssessmentSectionResource.php'"

echo AssessmentSection navigation labels restored!
echo Run: php artisan config:clear
pause
