{{-- body.end hook → the chat button (in place of the WhatsApp button) + the panel, inert until resources/js/chat.js opens it | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    $raised = View::hasSection('buy_bar');
    $name = \App\Core\Settings\Settings::get('chat.name') ?: 'دستیار '.config('khaneh.store.name');
    $config = [
        'history' => route('chat.history', absolute: false),
        'send' => route('chat.send', absolute: false),
        'token' => csrf_token(),
        'whatsapp' => \Modules\Chat\Support\Knowledge::whatsapp(),
    ];
@endphp
<script type="application/json" id="chat-config">@json($config)</script>
<button type="button" id="chat-launcher" aria-label="گفتگو با {{ $name }}" aria-expanded="false" aria-controls="chat-panel"
        @class([
            'fixed start-4 z-40 grid size-14 place-items-center rounded-full bg-rose text-white shadow-[0_14px_30px_-10px_var(--color-rose)] transition hover:scale-105 lg:bottom-6 lg:start-6',
            'bottom-24' => ! $raised,
            'bottom-44' => $raised,
        ])>
    <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12Z"/><path d="M8.5 11h.01M12 11h.01M15.5 11h.01" stroke-width="2.6"/></svg>
    <span class="absolute -top-0.5 -end-0.5 size-3.5 rounded-full border-2 border-white bg-ok" aria-hidden="true"></span>
</button>
<template id="chat-tpl">
    <section id="chat-panel" class="chat-panel glass-strong frost fixed inset-0 z-[65] flex flex-col overflow-hidden shadow-2xl sm:inset-auto sm:bottom-24 sm:start-6 sm:h-[min(600px,calc(100dvh-8rem))] sm:w-[380px] sm:rounded-[var(--radius-glass)]"
             role="dialog" aria-modal="false" aria-labelledby="chat-title">
        <header class="flex items-center gap-3 border-b border-line bg-gradient-to-l from-rose/10 to-caramel/10 px-4 py-3">
            <span class="grid size-10 shrink-0 place-items-center rounded-full bg-rose text-white">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3a4 4 0 0 1 4 4v1a4 4 0 0 1-8 0V7a4 4 0 0 1 4-4ZM5 21a7 7 0 0 1 14 0"/></svg>
            </span>
            <div class="min-w-0 flex-1">
                <p id="chat-title" class="truncate font-black text-navy">{{ $name }}</p>
                <p class="truncate text-xs text-ok">● آنلاین<span class="hidden sm:inline"> — معمولاً همان لحظه جواب می‌دهد</span></p>
            </div>
            @if ($config['whatsapp'])
                <a href="{{ $config['whatsapp'] }}" target="_blank" rel="noopener" class="grid size-9 place-items-center rounded-full text-[#25d366] hover:bg-[#25d366]/10" aria-label="واتس‌اپ">
                    <svg class="size-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm4.5 12.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.4.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.3.8 3.2.6.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.2-1.2-.1-.1-.3-.2-.5-.3Z"/></svg>
                </a>
            @endif
            <button type="button" class="grid size-9 place-items-center rounded-full text-muted hover:bg-navy/5" data-chat-close aria-label="بستن گفتگو">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </header>
        <div class="flex-1 space-y-3 overflow-y-auto overscroll-contain px-3 py-4" data-chat-log aria-live="polite"></div>
        <div class="flex gap-2 overflow-x-auto px-3 pb-2 [scrollbar-width:none]" data-chat-quick></div>
        <form class="flex items-end gap-2 border-t border-line bg-surface/70 p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))]" data-chat-form>
            <label class="sr-only" for="chat-input">پیام</label>
            <textarea id="chat-input" rows="1" maxlength="500" placeholder="پیام‌تان را بنویسید…" class="field max-h-28 min-h-11 flex-1 resize-none !py-2.5 text-sm"></textarea>
            <button type="submit" class="grid size-11 shrink-0 place-items-center rounded-full bg-rose text-white transition disabled:opacity-50" aria-label="ارسال">
                <svg class="size-5 -scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4 20-7Z"/></svg>
            </button>
        </form>
    </section>
</template>
