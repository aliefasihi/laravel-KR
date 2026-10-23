<?php

/**
 * Khaneh Romantic — Webhook for Telegram and Bale: answers at once, handles the update after the response
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Bot\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Bot\Support\Bot;
use Modules\Bot\Support\Channel;

use function Illuminate\Support\defer;

class WebhookController
{
    public function __invoke(Request $request, string $channel, string $secret): JsonResponse
    {
        $bot = Channel::get($channel);
        abort_unless($bot && $bot->enabled() && hash_equals($bot->secret(), $secret), 404);

        $update = $request->json()->all();
        defer(fn () => app(Bot::class)->receive($channel, $update));

        return response()->json(['ok' => true]);
    }
}
