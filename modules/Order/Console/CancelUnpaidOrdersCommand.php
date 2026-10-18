<?php

/**
 * Khaneh Romantic — orders:cancel-unpaid (scheduled every 5 minutes)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Order\Console;

use Illuminate\Console\Command;
use Modules\Order\Support\OrderWorkflow;

class CancelUnpaidOrdersCommand extends Command
{
    protected $signature = 'orders:cancel-unpaid';

    protected $description = 'Cancel unpaid online orders after the hold time and give their stock back';

    public function handle(OrderWorkflow $workflow): int
    {
        $this->info('Cancelled '.$workflow->cancelUnpaid().' unpaid order(s).');

        return self::SUCCESS;
    }
}
