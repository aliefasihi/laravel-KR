{{-- «Install the app» banner | نویسنده: علی فصیحی (Ali Fasihi) --}}
@if (\Modules\Pwa\Http\Controllers\PwaController::enabled() && \App\Core\Settings\Settings::get('pwa.install_banner', true) && ! View::hasSection('bare') && ! View::hasSection('buy_bar'))
    <div x-data="pwaInstall" x-cloak x-show="open" x-transition.opacity.duration.300ms
         class="glass !bg-surface/95 fixed inset-x-3 bottom-40 z-50 rounded-[var(--radius-glass)] p-4 shadow-2xl lg:inset-x-auto lg:bottom-6 lg:end-6 lg:w-96"
         style="margin-bottom: env(safe-area-inset-bottom)" role="dialog" aria-labelledby="pwa-title">
        <div class="flex items-start gap-3">
            <img src="{{ \Modules\Pwa\Support\AppIcons::urls()['192'] }}" alt="" class="size-12 shrink-0 rounded-2xl shadow" width="48" height="48">
            <div class="min-w-0 flex-1">
                <p id="pwa-title" class="font-black text-navy">اپلیکیشن {{ \Modules\Pwa\Http\Controllers\PwaController::name() }}</p>
                <p class="mt-1 text-sm leading-7 text-muted" x-show="! ios">نصب روی گوشی، بدون نوار مرورگر و با یک لمس از صفحه‌ی اصلی؛ بدون دانلود از استور.</p>
                <p class="mt-1 text-sm leading-7 text-muted" x-show="ios">
                    در سافاری دکمه‌ی اشتراک‌گذاری
                    <svg class="inline size-4 align-[-3px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-label="اشتراک‌گذاری"><path d="M12 15V3m0 0L8 7m4-4 4 4"/><path d="M8 11H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-2"/></svg>
                    و بعد «Add to Home Screen» را بزنید.
                </p>
            </div>
            <button type="button" @click="later()" class="-m-1 grid size-8 shrink-0 place-items-center rounded-full text-muted hover:bg-navy/5" aria-label="بستن">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </div>
        <div class="mt-3 flex gap-2" x-show="! ios">
            <button type="button" @click="install()" class="btn-rose flex-1">نصب اپلیکیشن</button>
            <button type="button" @click="later()" class="btn-glass">بعداً</button>
        </div>
    </div>
@endif
