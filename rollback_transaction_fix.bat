@echo off
echo Rolling back transaction endpoint changes...

REM Revert the TransactionController changes
git checkout HEAD -- app/Http/Controllers/Api/TransactionController.php

echo Transaction endpoint changes rolled back successfully!
echo The /my-transactions endpoint will now show individual TokenUsage records again.
pause
