{{-- The shared list: progress, items with «buy» / «I'll get this», done items ticked | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    use App\Core\Support\Money;
    use App\Core\Support\PersianText;
    use Modules\Registry\Support\RegistryPurchases;
    $wanted = $registry->items->sum('quantity');
    $done = $registry->items->sum(fn ($i) => min($i->quantity, $i->claimed()));
    $items = $registry->items->sortBy(fn ($i) => $i->remaining() === 0);   // still needed first
@endphp
<div class="space-y-6">
    <header class="glass relative isolate overflow-hidden rounded-[var(--radius-glass)] px-6 py-10 text-center sm:py-14">
        <div class="blob-duo -top-16 -start-10 -z-10 size-72" aria-hidden="true"></div>
        <svg class="mx-auto size-10 text-rose" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" aria-hidden="true"><path d="M20 12v8a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-8M3 8h18v4H3zM12 21V8M12 8S10.5 3 8 4s0 4 4 4ZM12 8s1.5-5 4-4 0 4-4 4Z"/></svg>
        <h1 class="mt-4 text-3xl font-black text-navy sm:text-4xl">{{ $registry->title }}</h1>
        @if ($registry->names || $registry->event_date)
            <p class="mt-2 text-muted">{{ $registry->names }}@if ($registry->names && $registry->event_date) · @endif{{ $registry->event_date ? \App\Core\Support\Jalali::format($registry->event_date) : '' }}</p>
        @endif
        @if ($registry->message)
            <p class="mx-auto mt-5 max-w-xl leading-8 text-navy">{{ $registry->message }}</p>
        @endif
        @if ($wanted > 0)
            <div class="mx-auto mt-7 max-w-sm">
                <div class="h-2 overflow-hidden rounded-full bg-navy/10"><div class="h-full rounded-full bg-rose" style="width: {{ round($done / $wanted * 100) }}%"></div></div>
                <p class="mt-2 text-xs text-muted">{{ PersianText::toPersianDigits($done) }} از {{ PersianText::toPersianDigits($wanted) }} قلم تهیه شده</p>
            </div>
        @endif
    </header>

    @if ($notice)
        <p class="glass rounded-2xl px-5 py-3 text-sm font-bold text-navy" role="status">{{ $notice }}</p>
    @endif

    @if ($items->isEmpty())
        <p class="py-12 text-center text-muted">این لیست هنوز خالی است.</p>
    @else
        <ul class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($items as $item)
                @php($variant = RegistryPurchases::variant($item))
                @php($left = $item->remaining())
                <li wire:key="ri-{{ $item->id }}" @class(['glass flex flex-col overflow-hidden rounded-[var(--radius-card)]', 'opacity-60' => $left === 0])>
                    <a href="{{ $item->product->url() }}" class="relative block aspect-[4/3] bg-cream">
                        <x-media.picture :media="$item->product->media->first()" :alt="$item->product->name" sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw" class="size-full object-cover" />
                        @if ($left === 0)
                            <span class="absolute inset-0 grid place-items-center bg-deep/40 text-lg font-black text-white">تهیه شد ✓</span>
                        @endif
                    </a>
                    <div class="flex flex-1 flex-col gap-2 p-4">
                        <span class="text-xs text-muted">{{ $item->product->brand?->name_fa }}</span>
                        <a href="{{ $item->product->url() }}" class="line-clamp-2 font-bold text-navy hover:text-rose">{{ $item->product->name }}</a>
                        <p class="text-xs text-muted">
                            @if ($item->quantity > 1)
                                {{ PersianText::toPersianDigits($item->claimed()) }} از {{ PersianText::toPersianDigits($item->quantity) }} عدد ·
                            @endif
                            {{ $variant ? Money::toman($variant->currentPrice()).' تومان' : 'فعلاً ناموجود در فروشگاه' }}
                        </p>
                        @if ($left > 0)
                            <div class="mt-auto flex gap-2 pt-2">
                                @if ($variant)
                                    <button type="button" wire:click="buy({{ $item->id }})" wire:loading.attr="disabled" class="btn-navy flex-1 !px-3 !py-2.5 text-xs">خرید این هدیه</button>
                                @endif
                                <button type="button" wire:click="$set('reserving', {{ $item->id }})" class="btn-glass flex-1 !px-3 !py-2.5 text-xs">رزرو می‌کنم</button>
                            </div>
                            @if ($reserving === $item->id)
                                <form wire:submit="reserve" class="space-y-2 rounded-2xl bg-cream/70 p-3">
                                    <p class="text-xs leading-6 text-muted">جای دیگری می‌خرید یا بعداً؟ نامتان را بنویسید تا کس دیگری همین را نخرد.</p>
                                    <input wire:model="name" type="text" placeholder="نام شما" class="field !py-2 text-sm" required>
                                    @error('name') <span class="block text-xs text-rose">{{ $message }}</span> @enderror
                                    <input wire:model="mobile" type="tel" inputmode="numeric" dir="ltr" placeholder="موبایل (اختیاری)" class="field !py-2 text-center text-sm">
                                    <div class="flex gap-2">
                                        <button type="submit" class="btn-navy flex-1 !py-2 text-xs">ثبت رزرو</button>
                                        <button type="button" wire:click="$set('reserving', null)" class="btn-glass !py-2 text-xs">انصراف</button>
                                    </div>
                                </form>
                            @endif
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
