<?php

/**
 * Khaneh Romantic — `php artisan push:keys`: shows the VAPID keys in use (for .env), or makes new ones
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Push\Console;

use Illuminate\Console\Command;
use Modules\Push\Support\WebPush;

class PushKeys extends Command
{
    protected $signature = 'push:keys {--new : make a new pair (every current subscription stops working)}';

    protected $description = 'Show (or create) the web-push VAPID keys';

    public function handle(): int
    {
        $keys = $this->option('new') ? WebPush::generateKeys() : WebPush::keys();
        $this->line('WEBPUSH_PUBLIC_KEY='.$keys['public']);
        $this->line('WEBPUSH_PRIVATE_KEY='.base64_encode($keys['private']));
        if ($this->option('new')) {
            $this->warn('Put both lines in .env and run php artisan config:clear. Every current subscriber will have to allow notifications again.');
        }

        return self::SUCCESS;
    }
}
