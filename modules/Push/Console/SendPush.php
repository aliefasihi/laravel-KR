<?php

/**
 * Khaneh Romantic — `php artisan push:send`: delivers waiting notifications (scheduled every minute)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Push\Console;

use Illuminate\Console\Command;
use Modules\Push\Support\Push;

class SendPush extends Command
{
    protected $signature = 'push:send {--seconds=45 : how long one run may take}';

    protected $description = 'Send waiting web-push notifications';

    public function handle(Push $push): int
    {
        $result = $push->process((int) $this->option('seconds'));
        $this->line("sent {$result['sent']}, failed {$result['failed']}, waiting {$result['waiting']}");

        return self::SUCCESS;
    }
}
