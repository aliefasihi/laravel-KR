{{-- Mobile floating glass navigation | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    $count = app(\Modules\Checkout\Support\Cart::class)->count();
    $items = [
        ['label' => 'خانه', 'url' => route('home'), 'active' => request()->routeIs('home'), 'icon' => 'M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1v-9.5Z'],
        ['label' => 'دسته‌بندی', 'url' => null, 'active' => request()->routeIs('catalog.category'), 'icon' => 'M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z'],
        ['label' => 'جستجو', 'url' => route('catalog.search'), 'active' => request()->routeIs('catalog.search'), 'icon' => 'M11 4.5a6.5 6.5 0 1 1 0 13 6.5 6.5 0 0 1 0-13ZM20 20l-4-4'],
        ['label' => 'سبد خرید', 'url' => route('checkout.cart'), 'active' => request()->routeIs('checkout.cart'), 'icon' => 'M5 8h14l-1.2 10.2A2 2 0 0 1 15.8 20H8.2a2 2 0 0 1-2-1.8L5 8Zm4 2V6a3 3 0 0 1 6 0v4', 'badge' => $count],
        // the account tab when the Customer module is on, otherwise the journal
        Route::has('customer.login')
            ? ['label' => auth('customer')->check() ? 'حساب من' : 'ورود', 'url' => route(auth('customer')->check() ? 'customer.account' : 'customer.login'), 'active' => request()->routeIs('customer.*'), 'icon' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-8 9a8 8 0 0 1 16 0']
            : ['label' => 'مجله', 'url' => route('content.blog'), 'active' => request()->routeIs('content.*'), 'icon' => 'M5 4h11a3 3 0 0 1 3 3v13H8a3 3 0 0 1-3-3V4Zm3 4h8M8 12h8M8 16h5'],
    ];
@endphp

<nav class="fixed inset-x-3 bottom-3 z-40 lg:hidden" style="padding-bottom: env(safe-area-inset-bottom)" aria-label="ناوبری پایین"
     x-data="{ count: {{ $count }} }" @cart-count.window="count = $event.detail.count">
    <ul class="glass-strong frost mx-auto flex max-w-md items-stretch justify-between rounded-[1.6rem] px-2 py-1.5">
        @foreach ($items as $item)
            <li class="flex-1">
                @php($classes = ['relative flex w-full flex-col items-center gap-1 rounded-2xl py-1.5 text-[11px] font-bold transition', 'text-rose' => $item['active'], 'text-navy/70' => ! $item['active']])
                @if ($item['url'])
                    <a href="{{ $item['url'] }}" @class($classes) @if ($item['active']) aria-current="page" @endif>
                @else
                    <button type="button" @class($classes) @click="$dispatch('open-menu')">
                @endif
                    @if ($item['active'])
                        <span class="absolute -top-1.5 h-1 w-6 rounded-full bg-rose" aria-hidden="true"></span>
                    @endif
                    <span class="relative">
                        <svg class="size-[22px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $item['icon'] }}"/></svg>
                        @isset($item['badge'])
                            <span x-show="count > 0" x-text="toFa(count)" @if (! $item['badge']) x-cloak @endif
                                  class="absolute -top-1.5 -end-2 grid min-w-4 place-items-center rounded-full bg-rose px-1 text-[10px] leading-4 text-white">{{ \App\Core\Support\PersianText::toPersianDigits($item['badge']) }}</span>
                        @endisset
                    </span>
                    {{ $item['label'] }}
                @if ($item['url'])
                    </a>
                @else
                    </button>
                @endif
            </li>
        @endforeach
    </ul>
</nav>
