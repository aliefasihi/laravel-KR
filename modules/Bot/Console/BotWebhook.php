<?php

/**
 * Khaneh Romantic — `php artisan bot:webhook {--off}`: points Telegram/Bale at this site (or stops it)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Bot\Console;

use Illuminate\Console\Command;
use Modules\Bot\Support\Channel;

class BotWebhook extends Command
{
    protected $signature = 'bot:webhook {--off : remove the webhook (for polling)}';

    protected $description = 'Set (or remove) the Telegram/Bale bot webhooks';

    public function handle(): int
    {
        foreach (Channel::all() as $channel) {
            $ok = $this->option('off') ? $channel->deleteWebhook() : $channel->setWebhook();
            $this->line($channel->name.': '.($ok ? 'ok' : 'failed').($this->option('off') ? '' : ' → '.$channel->webhookUrl()));
        }

        return self::SUCCESS;
    }
}
