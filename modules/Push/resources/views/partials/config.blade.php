{{-- body.end hook → web push settings for resources/js/push.js + the «allow notifications?» card | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    use Modules\Push\Support\Push;
    use Modules\Push\Support\WebPush;
    $route = (string) request()->route()?->getName();
    $quiet = str_starts_with($route, 'order.') || str_starts_with($route, 'checkout.') || str_starts_with($route, 'payment.');
    $config = [
        'key' => WebPush::keys()['public'],
        'subscribe' => route('push.subscribe'),
        'unsubscribe' => route('push.unsubscribe'),
        'token' => csrf_token(),
        'customer' => auth('customer')->id(),
        'prompt' => [
            'on' => ! $quiet && (bool) \App\Core\Settings\Settings::get('push.prompt', true),
            'views' => max(1, (int) \App\Core\Settings\Settings::get('push.prompt_views', 2)),
            'delay' => max(0, (int) \App\Core\Settings\Settings::get('push.prompt_delay', 15)),
            'days' => 14,
        ],
    ];
@endphp
<script type="application/json" id="push-config">@json($config)</script>
@if ($config['prompt']['on'])
    <template id="push-prompt-tpl">
        <div class="push-prompt glass-strong frost fixed inset-x-3 bottom-[5.75rem] z-[60] mx-auto max-w-sm rounded-[var(--radius-glass)] p-4 shadow-2xl sm:inset-x-auto sm:start-6 sm:bottom-6 lg:bottom-6" role="dialog" aria-labelledby="push-prompt-title">
            <div class="flex items-start gap-3">
                <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-rose/10 text-rose">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                </span>
                <div class="min-w-0">
                    <p id="push-prompt-title" class="font-black text-navy">{{ \App\Core\Settings\Settings::get('push.prompt_title') ?: 'از تخفیف‌ها جا نمانید' }}</p>
                    <p class="mt-1 text-sm leading-6 text-muted">{{ \App\Core\Settings\Settings::get('push.prompt_text') ?: 'حراج‌ها، کالکشن‌های تازه و خبر سفارش‌تان را با نوتیفیکیشن بگیرید. هر وقت خواستید خاموشش کنید.' }}</p>
                </div>
            </div>
            <div class="mt-3 flex justify-end gap-2">
                <button type="button" class="btn-glass !px-4 !py-2 text-sm" data-push-later>بعداً</button>
                <button type="button" class="btn-rose !px-4 !py-2 text-sm" data-push-yes>بله، خبرم کن</button>
            </div>
        </div>
    </template>
@endif
