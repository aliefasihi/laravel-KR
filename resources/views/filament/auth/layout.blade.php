{{-- Admin login layout: form + brand panel | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php($store = config('khaneh.store'))
<x-filament-panels::layout.base :livewire="$livewire ?? null">
    <div class="kr-auth grid min-h-dvh bg-[#fbf7f5] lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)] dark:bg-gray-950">

        {{-- Form side --}}
        <main id="fi-main-content" class="relative flex flex-col px-6 py-8 sm:px-12">
            <div class="pointer-events-none absolute -top-24 -end-24 size-80 rounded-full bg-[radial-gradient(closest-side,rgb(204_51_102/0.14),transparent)] lg:hidden" aria-hidden="true"></div>

            <a href="{{ url('/') }}" class="flex items-center gap-3 self-start" aria-label="{{ $store['name'] }}">
                <span class="grid size-11 place-items-center rounded-full bg-navy text-caramel shadow-[0_8px_20px_-10px_rgb(35_40_79/0.9)]">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 19c-2.6-2-3.8-4.4-3.8-7.2S9.5 6.6 12 4.5c2.5 2.1 3.8 4.5 3.8 7.3S14.6 17 12 19Z"/>
                        <path d="M12 19c-3.9-.3-6.8-2.2-8-5.6 2.4-1 4.6-.9 6.4.2M12 19c3.9-.3 6.8-2.2 8-5.6-2.4-1-4.6-.9-6.4.2"/>
                    </svg>
                </span>
                <span class="leading-tight">
                    <span class="block text-lg font-black text-navy dark:text-white">{{ $store['name'] }}</span>
                    <span class="block text-[10px] tracking-[0.18em] text-[#cf8d4c] uppercase" dir="ltr">{{ $store['name_en'] }}</span>
                </span>
            </a>

            <div class="flex flex-1 items-center justify-center py-10">
                <div class="kr-auth-card w-full max-w-[26rem] rounded-[1.75rem] bg-white/80 p-7 shadow-[0_24px_60px_-28px_rgb(35_40_79/0.35)] ring-1 ring-black/5 sm:p-9 dark:bg-white/5 dark:ring-white/10">
                    {{ $slot }}
                </div>
            </div>

            <p class="text-center text-xs text-gray-500 dark:text-gray-400">
                <a href="{{ url('/') }}" class="hover:text-rose">← بازگشت به فروشگاه</a>
            </p>
        </main>

        {{-- Brand side --}}
        <aside class="relative m-3 hidden overflow-hidden rounded-[2rem] bg-[linear-gradient(150deg,#2e3466,#23284f_55%,#1a1e3d)] text-white lg:flex lg:flex-col lg:justify-between lg:p-12" aria-hidden="true">
            <div class="absolute -top-32 -start-24 size-[34rem] rounded-full bg-[radial-gradient(closest-side,rgb(204_51_102/0.45),transparent)]"></div>
            <div class="absolute -bottom-40 -end-24 size-[38rem] rounded-full bg-[radial-gradient(closest-side,rgb(228_169_108/0.35),transparent)]"></div>
            <svg class="absolute inset-0 size-full opacity-[0.07]" aria-hidden="true">
                <defs><pattern id="kr-dots" width="22" height="22" patternUnits="userSpaceOnUse"><circle cx="2" cy="2" r="1.4" fill="#fff"/></pattern></defs>
                <rect width="100%" height="100%" fill="url(#kr-dots)"/>
            </svg>

            <div class="relative">
                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-1.5 text-xs font-bold ring-1 ring-white/15">
                    <span class="size-1.5 rounded-full bg-[#e4a96c]"></span> پنل مدیریت فروشگاه
                </span>
            </div>

            <div class="relative space-y-6">
                <svg class="size-20 text-[#e4a96c]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="0.9" stroke-linejoin="round">
                    <path d="M12 19c-2.6-2-3.8-4.4-3.8-7.2S9.5 6.6 12 4.5c2.5 2.1 3.8 4.5 3.8 7.3S14.6 17 12 19Z"/>
                    <path d="M12 19c-3.9-.3-6.8-2.2-8-5.6 2.4-1 4.6-.9 6.4.2M12 19c3.9-.3 6.8-2.2 8-5.6-2.4-1-4.6-.9-6.4.2"/>
                </svg>
                <h2 class="text-4xl leading-[1.4] font-black">
                    هر روز، یک تجربه‌ی <span class="text-[#e4a96c]">تازه</span><br>برای خانه‌های ایرانی
                </h2>
                <p class="max-w-md text-sm leading-7 text-white/70">{{ $store['tagline'] }} — محصولات، سفارش‌ها، بنرها و محتوای فروشگاه از همین‌جا مدیریت می‌شود.</p>
            </div>

            <div class="relative grid grid-cols-3 gap-3">
                @foreach ([['ضمانت اصالت', 'M12 3 4.5 6v5.5c0 4.6 3.2 8.2 7.5 9.5 4.3-1.3 7.5-4.9 7.5-9.5V6L12 3Z'], ['ارسال سراسری', 'M3 7h11v9H3zM14 10h4l3 3v3h-7M7 19a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Zm10 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z'], ['پرداخت امن', 'M3 7h18v10H3zM3 11h18']] as [$label, $path])
                    <div class="rounded-2xl bg-white/[0.07] p-4 ring-1 ring-white/10">
                        <svg class="mb-3 size-6 text-[#e4a96c]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"><path d="{{ $path }}"/></svg>
                        <span class="text-sm font-bold">{{ $label }}</span>
                    </div>
                @endforeach
            </div>
        </aside>
    </div>
</x-filament-panels::layout.base>
