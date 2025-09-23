@echo off
echo Rolling back institution transactions endpoint changes...

REM Revert the TransactionController changes
git checkout HEAD -- app/Http/Controllers/Api/TransactionController.php

echo Institution transactions endpoint changes rolled back successfully!
echo The /api/institution/transactions endpoint will now only show student transactions again.
pause
