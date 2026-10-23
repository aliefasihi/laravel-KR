<?php

/**
 * Khaneh Romantic — Messages the bot sends by itself (new order…) to everyone linked, in both apps
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Bot\Support;

use App\Core\Support\Money;
use App\Core\Support\PersianText;
use Modules\Bot\Models\BotOperator;

final class Notifier
{
    public function broadcast(string $text, string $only = 'notify_orders'): int
    {
        $sent = 0;
        foreach (BotOperator::query()->where('is_active', true)->where($only, true)->get() as $op) {
            foreach ($op->chats() as $channelName => $chatId) {
                $channel = Channel::get($channelName);
                if ($channel?->enabled()) {
                    (new Out($channel, (string) $chatId, $op->id))->say($text);
                    $sent++;
                }
            }
        }

        return $sent;
    }

    /** @param  array<string, mixed>  $order  OrderEvents payload */
    public function newOrder(array $order, string $title = '🛒 سفارش جدید'): void
    {
        $admin = rtrim((string) config('app.url'), '/').'/admin/orders/'.$order['id'];
        $this->broadcast(implode("\n", [
            $title.' #'.PersianText::toPersianDigits((string) $order['number']),
            '👤 '.($order['name'] ?? '—').' · '.PersianText::toPersianDigits((string) ($order['mobile'] ?? '')),
            '💰 '.Money::toman((int) $order['total']).' تومان',
            '🛠 '.$admin,
        ]));
    }
}
