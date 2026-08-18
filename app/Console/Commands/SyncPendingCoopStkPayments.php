<?php

namespace App\Console\Commands;

use App\Services\CoopPaymentSyncService;
use Illuminate\Console\Command;

class SyncPendingCoopStkPayments extends Command
{
    protected $signature = 'coop:sync-pending-stk {--minutes=15 : Ignore STK payments older than this}';

    protected $description = 'Poll Co-op STK Transaction Status for pending token purchases and credit completed ones';

    public function handle(CoopPaymentSyncService $sync): int
    {
        $minutes = max(1, (int) $this->option('minutes'));
        $synced = $sync->syncPendingStk($minutes);

        $this->info("Checked {$synced} pending Co-op STK payment(s).");

        return self::SUCCESS;
    }
}
