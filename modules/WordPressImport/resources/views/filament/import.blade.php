{{-- Admin: WordPress import | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    use App\Core\Support\{Jalali, PersianText};
    use Modules\WordPressImport\Models\ImportRun;
    $fa = fn ($n) => $n === null ? '—' : PersianText::toPersianDigits(number_format((int) $n));
    $connection = $this->connection();
    $rows = $this->stepRows();
    $run = $this->run();
    $labels = collect($rows)->pluck('label', 'key')->put('media', 'تصاویر');
    $countLabels = ['created' => 'تازه', 'updated' => 'به‌روز', 'skipped' => 'رد شد', 'failed' => 'خطا'];
    $history = $this->history();
@endphp
<x-filament-panels::page>
    <div @if ($run?->isActive()) wire:poll.3s @endif class="grid gap-6">

        {{-- connection --}}
        <x-filament::section>
            <x-slot name="heading">اتصال به سایت فعلی</x-slot>
            <x-slot name="afterHeader">
                <x-filament::button size="sm" color="gray" wire:click="refreshConnection" icon="heroicon-m-arrow-path">بررسی دوباره</x-filament::button>
            </x-slot>
            @if ($connection['error'])
                <div class="rounded-lg bg-danger-50 p-3 text-sm text-danger-700 dark:bg-danger-500/10 dark:text-danger-400">
                    اتصال برقرار نشد. مقادیر <code dir="ltr">WP_DB_*</code> را در فایل <code>.env</code> بررسی کنید.
                    <div class="mt-1 font-mono text-xs" dir="ltr">{{ \Illuminate\Support\Str::limit($connection['error'], 300) }}</div>
                </div>
            @else
                <div class="mb-3 flex flex-wrap gap-2">
                    <x-filament::badge color="success">متصل</x-filament::badge>
                    <x-filament::badge color="info">{{ $connection['hpos'] ? 'سفارش‌ها: HPOS' : 'سفارش‌ها: ذخیره‌سازی کلاسیک' }}</x-filament::badge>
                </div>
            @endif
            <dl class="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
                @foreach ($connection['info'] as $k => $v)
                    <div class="flex gap-2"><dt class="shrink-0 text-gray-500 dark:text-gray-400">{{ $k }}:</dt><dd class="min-w-0 break-all">{{ $v }}</dd></div>
                @endforeach
            </dl>
        </x-filament::section>

        {{-- steps --}}
        <x-filament::section>
            <x-slot name="heading">مرحله‌ها</x-slot>
            <x-slot name="description">چیزی انتخاب نکنید تا همه به ترتیب اجرا شوند. اجرای دوباره تکراری نمی‌سازد، فقط به‌روز می‌کند.</x-slot>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-gray-500 dark:border-white/10 dark:text-gray-400">
                            <th class="w-10 py-2"></th>
                            <th class="py-2 text-start font-medium">مرحله</th>
                            <th class="py-2 text-start font-medium">در وردپرس</th>
                            <th class="py-2 text-start font-medium">این‌جا</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr class="border-b border-gray-100 last:border-0 dark:border-white/5" wire:key="step-{{ $row['key'] }}">
                                <td class="py-2">
                                    <input type="checkbox" value="{{ $row['key'] }}" wire:model="selected" @disabled(! $row['available'])
                                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-white/20 dark:bg-white/5">
                                </td>
                                <td class="py-2">
                                    <span class="font-medium">{{ $row['label'] }}</span>
                                    <span class="ms-1 font-mono text-xs text-gray-400" dir="ltr">{{ $row['key'] }}</span>
                                    @unless ($row['available'])
                                        <x-filament::badge color="gray" size="sm" class="ms-1 inline-flex">ماژول {{ implode('، ', $row['requires']) }} خاموش است</x-filament::badge>
                                    @endunless
                                </td>
                                <td class="py-2 tabular-nums">{{ $fa($row['source']) }}</td>
                                <td class="py-2 tabular-nums">{{ $row['key'] === 'links' ? '—' : $fa($row['imported']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex flex-wrap items-end gap-4">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model="dryRun" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-white/20 dark:bg-white/5">
                    فقط پیش‌نمایش (چیزی نوشته نمی‌شود)
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="text-gray-500 dark:text-gray-400">محدودیت برای آزمایش (اختیاری)</span>
                    <x-filament::input.wrapper class="w-40">
                        <x-filament::input type="number" min="1" wire:model="limit" placeholder="مثلاً ۲۰" />
                    </x-filament::input.wrapper>
                </label>
                <x-filament::button wire:click="start" icon="heroicon-m-play" :disabled="(bool) ($run?->isActive() || $connection['error'])">شروع</x-filament::button>
            </div>
            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                برای درون‌ریزی کامل اول (هزاران محصول و سفارش) اجرا از ترمینال سریع‌تر و مطمئن‌تر است:
                <code dir="ltr" class="rounded bg-gray-100 px-1 dark:bg-white/10">php artisan wp:import</code>
            </p>
        </x-filament::section>

        {{-- current / selected run --}}
        @if ($run)
            <x-filament::section>
                <x-slot name="heading">
                    اجرای #{{ PersianText::toPersianDigits($run->id) }}
                    {{ $run->dry_run ? '(پیش‌نمایش)' : '' }}
                </x-slot>
                <x-slot name="afterHeader">
                    <x-filament::badge :color="match ($run->status) { 'done' => 'success', 'failed' => 'danger', 'running' => 'warning', default => 'gray' }">
                        {{ ImportRun::STATUSES[$run->status] ?? $run->status }}
                        @if ($run->current_step) — {{ $labels[$run->current_step] ?? $run->current_step }} @endif
                    </x-filament::badge>
                </x-slot>
                @if ($run->error)
                    <div class="mb-3 rounded-lg bg-danger-50 p-3 text-sm text-danger-700 dark:bg-danger-500/10 dark:text-danger-400">{{ $run->error }}</div>
                @endif
                @if ($run->counts)
                    <div class="mb-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($run->counts as $step => $counts)
                            <div class="rounded-lg border border-gray-200 p-3 dark:border-white/10">
                                <div class="mb-1 text-sm font-medium">{{ $labels[$step] ?? $step }}</div>
                                <div class="flex flex-wrap gap-x-3 text-xs text-gray-600 dark:text-gray-300">
                                    @foreach ($counts as $what => $n)
                                        <span @class(['text-danger-600 dark:text-danger-400' => $what === 'failed'])>{{ $countLabels[$what] ?? $what }}: {{ $fa($n) }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
                <div class="max-h-96 overflow-y-auto rounded-lg bg-gray-50 p-3 font-mono text-xs leading-6 dark:bg-white/5">
                    @forelse (array_slice($run->log ?? [], -300) as $line)
                        <div @class(['text-warning-700 dark:text-warning-400' => $line['level'] === 'warning'])>
                            <span class="text-gray-400">[{{ $line['step'] }}]</span> {{ $line['message'] }}
                        </div>
                    @empty
                        <div class="text-gray-400">{{ $run->isActive() ? 'در انتظار شروع… (queue worker روشن است؟)' : 'لاگی نیست.' }}</div>
                    @endforelse
                </div>
            </x-filament::section>
        @endif

        {{-- history --}}
        @if ($history->count() > 1)
            <x-filament::section collapsible collapsed>
                <x-slot name="heading">اجراهای قبلی</x-slot>
                <div class="grid gap-1 text-sm">
                    @foreach ($history as $item)
                        <button type="button" wire:click="showRun({{ $item->id }})" class="flex flex-wrap items-center gap-2 rounded-lg px-2 py-1 text-start hover:bg-gray-50 dark:hover:bg-white/5">
                            <span class="font-medium">#{{ PersianText::toPersianDigits($item->id) }}</span>
                            <span class="text-gray-500 dark:text-gray-400">{{ Jalali::format($item->created_at) }}</span>
                            <span>{{ $item->steps ? collect($item->steps)->map(fn ($s) => $labels[$s] ?? $s)->implode('، ') : 'همه‌ی مرحله‌ها' }}</span>
                            @if ($item->dry_run) <x-filament::badge size="sm" color="gray">پیش‌نمایش</x-filament::badge> @endif
                            <x-filament::badge size="sm" :color="match ($item->status) { 'done' => 'success', 'failed' => 'danger', default => 'warning' }">{{ ImportRun::STATUSES[$item->status] ?? $item->status }}</x-filament::badge>
                        </button>
                    @endforeach
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
