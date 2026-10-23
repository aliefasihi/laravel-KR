<?php

/**
 * Khaneh Romantic — `php artisan bot:poll`: fetches updates instead of a webhook (for servers Telegram/Bale
 * can't call). Runs ~55 s from the scheduler every minute when «حالت دریافت» is «polling».
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Bot\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Modules\Bot\Support\Bot;
use Modules\Bot\Support\Channel;

class BotPoll extends Command
{
    protected $signature = 'bot:poll {--seconds=55}';

    protected $description = 'Fetch and handle Telegram/Bale bot updates (polling mode)';

    public function handle(Bot $bot): int
    {
        $channels = Channel::all();
        if ($channels === []) {
            $this->line('no bot is set up');

            return self::SUCCESS;
        }
        $until = time() + (int) $this->option('seconds');
        $handled = 0;
        do {
            foreach ($channels as $channel) {
                $key = 'bot.offset.'.$channel->name;
                $wait = count($channels) > 1 ? 5 : max(1, min(25, $until - time() - 2));
                $updates = $channel->updates((int) Cache::get($key, 0), $wait) ?? [];
                foreach ($updates as $update) {
                    Cache::forever($key, (int) $update['update_id'] + 1);
                    $bot->receive($channel->name, $update);
                    $handled++;
                }
            }
        } while (time() < $until - 3);
        $this->line("handled {$handled}");

        return self::SUCCESS;
    }
}
