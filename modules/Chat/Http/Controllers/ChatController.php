<?php

/**
 * Khaneh Romantic — Site chat: the conversation so far, and one message → the assistant's replies
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Chat\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\Chat\Models\ChatConversation;
use Modules\Chat\Support\Assistant;
use Modules\Chat\Support\Chat;

class ChatController
{
    public function history(Request $request): JsonResponse
    {
        abort_unless(Chat::enabled(), 404);
        $conversation = $this->find((string) $request->query('token'));
        $messages = $conversation
            ? $conversation->messages()->reorder('id', 'desc')->limit(40)->get()->reverse()->map->toWidget()->values()->all()
            : [];

        return response()->json(['messages' => [Assistant::greeting(), ...$messages]]);
    }

    public function send(Request $request): JsonResponse
    {
        abort_unless(Chat::enabled(), 404);
        $text = trim((string) $request->input('text'));
        if ($text === '') {
            return response()->json(['messages' => []], 422);
        }
        $conversation = $this->find((string) $request->input('token')) ?? ChatConversation::query()->create([
            'token' => Str::random(40),
            'page' => mb_substr((string) parse_url((string) $request->input('page'), PHP_URL_PATH), 0, 250) ?: null,
            'ip' => $request->ip(),
        ]);
        if (! $conversation->customer_id && ($customer = Auth::guard('customer')->id())) {
            $conversation->forceFill(['customer_id' => $customer])->save();
        }
        if ($conversation->messages_count >= 200) {
            return response()->json(['token' => $conversation->token, 'messages' => [['role' => 'bot', 'text' => 'این گفتگو خیلی طولانی شد؛ لطفاً با پشتیبان صحبت کنید.']]]);
        }

        return response()->json([
            'token' => $conversation->token,
            'messages' => (new Assistant($conversation))->reply($text),
        ]);
    }

    private function find(string $token): ?ChatConversation
    {
        return preg_match('/^[A-Za-z0-9]{40}$/', $token) ? ChatConversation::query()->where('token', $token)->first() : null;
    }
}
