{{-- Admin: «سرعت سایت» — live checks of what makes this install fast | نویسنده: علی فصیحی (Ali Fasihi) --}}
<x-filament-panels::page>
    @php
        $checks = $this->getChecks();
        $score = \App\Core\Support\SpeedCheck::score($checks);
    @endphp
    <div class="flex flex-wrap items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-gray-900">
        <span @class(['grid size-16 shrink-0 place-items-center rounded-full text-xl font-black text-white',
            'bg-success-600' => $score >= 90, 'bg-warning-600' => $score >= 60 && $score < 90, 'bg-danger-600' => $score < 60])>{{ \App\Core\Support\PersianText::toPersianDigits($score) }}</span>
        <div class="min-w-0 flex-1 text-sm leading-7 text-gray-600 dark:text-gray-300">
            <p class="font-bold text-gray-900 dark:text-white">آمادگی سرور برای سرعت بالا</p>
            <p>کد سایت برای PageSpeed بهینه شده است؛ این بخش تنظیمات خود سرور را می‌سنجد. موارد قرمز را اول درست کنید.</p>
        </div>
    </div>

    <div class="divide-y divide-gray-100 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:divide-white/5 dark:border-white/10 dark:bg-gray-900">
        @foreach ($checks as $check)
            <div class="flex items-start gap-3 p-4">
                <span @class(['mt-1 size-3 shrink-0 rounded-full',
                    'bg-success-500' => $check['state'] === 'ok', 'bg-warning-500' => $check['state'] === 'warn', 'bg-danger-500' => $check['state'] === 'bad'])></span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <p class="font-bold text-gray-900 dark:text-white">{{ $check['label'] }}</p>
                        <span class="text-sm text-gray-500 dark:text-gray-400" dir="auto">{{ $check['value'] }}</span>
                    </div>
                    @if ($check['state'] !== 'ok')
                        <p class="mt-1 text-sm leading-7 text-gray-600 dark:text-gray-300">{{ $check['hint'] }}</p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
