{{-- Admin: WordPress import | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    use App\Core\Support\{Jalali, PersianText};
    use Illuminate\Support\Number;
    use Modules\Order\Models\Order;
    use Modules\WordPressImport\Models\{ImportRun, ImportSource};
    use Modules\WordPressImport\Support\Dump\Sources;
    use Modules\WordPressImport\Support\SourceAnalyzer;
    $fa = fn ($n) => $n === null ? '—' : PersianText::toPersianDigits(number_format((int) $n));
    $bytes = fn ($n) => PersianText::toPersianDigits(Number::fileSize((int) $n, 1));
    $sources = $this->sources();
    $source = $this->source();
    $envError = $this->envError();
    $detected = $this->detected();
    $rows = $this->stepRows();
    $run = $this->run();
    $incoming = Sources::incomingFiles();
    $labels = collect($rows)->pluck('label', 'key')->put('media', 'تصاویر')->put('attributes', 'ویژگی‌ها');
    $countLabels = ['created' => 'تازه', 'updated' => 'به‌روز', 'skipped' => 'رد شد', 'failed' => 'خطا'];
    $history = $this->history();
    $loading = $source && in_array($source->status, ['uploaded', 'loading'], true);
    $countNames = ['products' => 'محصول', 'variations' => 'مدل (ورییشن)', 'posts' => 'مقاله', 'pages' => 'برگه', 'attachments' => 'فایل رسانه',
        'users' => 'کاربر', 'orders' => 'سفارش', 'coupons' => 'کد تخفیف', 'reviews' => 'نظر محصول'];
    $field = 'rounded-lg border-none bg-white px-3 py-1.5 text-sm text-gray-950 shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/20';
    $input = 'block w-full '.$field;
@endphp
<x-filament-panels::page>
    <div @if ($run?->isActive() && ! $this->waitingForWorker()) wire:poll.3s @endif class="grid gap-6">

        {{-- 1. database --}}
        <x-filament::section>
            <x-slot name="heading">۱. پایگاه داده‌ی وردپرس</x-slot>
            <x-slot name="description">از phpMyAdmin (یا هاست/افزونه‌ی پشتیبان) خروجی کامل دیتابیس سایت فعلی را بگیرید و این‌جا آپلود کنید: .sql، .sql.gz یا .zip — حجم مهم نیست.</x-slot>

            <div
                x-data="{
                    url: @js(route('wordpressimport.upload')),
                    csrf: @js(csrf_token()),
                    chunk: {{ $this->chunkSize() }},
                    busy: false, percent: 0, message: '', over: false,
                    async upload(file) {
                        if (! file) return;
                        if (! /\.(sql|sql\.gz|gz|zip)$/i.test(file.name)) { this.message = 'فقط فایل .sql، .sql.gz یا .zip'; return; }
                        this.busy = true; this.percent = 0; this.message = 'در حال آپلود ' + file.name + '…';
                        const id = (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : (Date.now() + '-' + Math.random().toString(36).slice(2));
                        let offset = 0, size = this.chunk, tries = 0;
                        while (offset < file.size) {
                            const query = new URLSearchParams({ upload: id, name: file.name, size: file.size, offset: offset });
                            let res;
                            try {
                                res = await fetch(this.url + '?' + query, { method: 'POST', credentials: 'same-origin',
                                    headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json', 'Content-Type': 'application/octet-stream' },
                                    body: file.slice(offset, Math.min(offset + size, file.size)) });
                            } catch (e) {
                                if (++tries > 6) { this.fail('اتصال قطع شد؛ دوباره فایل را انتخاب کنید.'); return; }
                                await new Promise(r => setTimeout(r, 1500 * tries)); continue;
                            }
                            if (res.status === 413 && size > 262144) { size = Math.floor(size / 2); continue; }
                            let data = {}; try { data = await res.json(); } catch (e) {}
                            if (res.status === 409 && data.offset !== undefined) { offset = data.offset; continue; }
                            if (! res.ok) { this.fail(data.message || ('خطای سرور (' + res.status + ')')); return; }
                            tries = 0; offset = data.offset; this.percent = Math.floor(offset * 100 / file.size);
                            if (data.done) { this.busy = false; this.message = ''; await this.$wire.uploaded(data.id); return; }
                        }
                    },
                    fail(m) { this.busy = false; this.message = m; },
                }"
                class="grid gap-4"
            >
                <label
                    x-on:dragover.prevent="over = true" x-on:dragleave.prevent="over = false"
                    x-on:drop.prevent="over = false; upload($event.dataTransfer.files[0])"
                    :class="over ? 'border-primary-500 bg-primary-50 dark:bg-primary-500/10' : 'border-gray-300 dark:border-white/15'"
                    class="flex cursor-pointer flex-col items-center gap-2 rounded-xl border-2 border-dashed p-6 text-center transition"
                >
                    <x-filament::icon icon="heroicon-o-circle-stack" class="h-8 w-8 text-gray-400" />
                    <span class="text-sm font-medium">فایل را این‌جا رها کنید یا کلیک کنید</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">.sql · .sql.gz · .zip — تکه‌تکه آپلود می‌شود، محدودیت حجم هاست مهم نیست</span>
                    <input type="file" accept=".sql,.gz,.zip" class="hidden" x-on:change="upload($event.target.files[0]); $event.target.value = ''" :disabled="busy">
                </label>
                <div x-show="busy || message" x-cloak class="grid gap-1">
                    <div x-show="busy" class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                        <div class="h-full rounded-full bg-primary-600 transition-all" :style="`width: ${percent}%`"></div>
                    </div>
                    <div class="flex justify-between text-xs text-gray-600 dark:text-gray-300">
                        <span x-text="message"></span><span x-show="busy" x-text="percent + '٪'"></span>
                    </div>
                </div>
            </div>

            @if ($incoming)
                <div class="mt-4 rounded-lg bg-gray-50 p-3 text-sm dark:bg-white/5">
                    <div class="mb-2 text-gray-600 dark:text-gray-300">فایل‌هایی که روی سرور در <code dir="ltr">storage/app/private/wp-imports/incoming</code> گذاشته‌اید:</div>
                    @foreach ($incoming as $file)
                        <div class="flex flex-wrap items-center gap-2 py-1" wire:key="in-{{ $file['name'] }}">
                            <span class="font-mono text-xs" dir="ltr">{{ $file['name'] }}</span>
                            <span class="text-xs text-gray-500">{{ $bytes($file['size']) }}</span>
                            <x-filament::button size="xs" color="gray" wire:click="useIncoming(@js($file['name']))">استفاده</x-filament::button>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- available databases --}}
            <div class="mt-4 grid gap-2">
                @foreach ($sources as $item)
                    @php($active = $this->sourceKey === (string) $item->id)
                    <div wire:key="src-{{ $item->id }}" @class([
                        'flex flex-wrap items-center gap-3 rounded-xl border p-3',
                        'border-primary-500 ring-1 ring-primary-500' => $active,
                        'border-gray-200 dark:border-white/10' => ! $active,
                    ])>
                        <button type="button" wire:click="selectSource('{{ $item->id }}')" class="flex min-w-0 flex-1 items-center gap-3 text-start">
                            <span @class(['flex h-4 w-4 shrink-0 items-center justify-center rounded-full border', 'border-primary-600' => $active, 'border-gray-300 dark:border-white/30' => ! $active])>
                                @if ($active) <span class="h-2 w-2 rounded-full bg-primary-600"></span> @endif
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-medium" dir="ltr">{{ $item->name }}</span>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">
                                    {{ $bytes($item->size) }} · {{ Jalali::format($item->created_at) }}
                                    @if ($item->detected) · {{ $item->detected['site']['home'] ?? '' }} @endif
                                    @if ($item->prefix) · پیشوند <span dir="ltr">{{ $item->prefix }}</span> @endif
                                </span>
                            </span>
                        </button>
                        <x-filament::badge :color="match ($item->status) { 'ready' => 'success', 'failed' => 'danger', default => 'warning' }">
                            {{ ImportSource::STATUSES[$item->status] ?? $item->status }}@if (in_array($item->status, ['uploaded', 'loading'], true)) {{ PersianText::toPersianDigits($item->progress()) }}٪@endif
                        </x-filament::badge>
                        <x-filament::icon-button icon="heroicon-m-trash" color="gray" size="sm" label="حذف"
                            wire:click="deleteSource({{ $item->id }})" wire:confirm="این پایگاه داده‌ی آپلودشده حذف شود؟ (چیزهایی که درون‌ریزی شده‌اند می‌مانند)" />
                        @if ($item->status === 'failed')
                            <div class="w-full text-xs text-danger-600 dark:text-danger-400">{{ \Illuminate\Support\Str::limit($item->error, 300) }}</div>
                        @endif
                    </div>
                @endforeach

                {{-- the WP_DB_* connection --}}
                <div wire:key="src-env" @class([
                    'flex flex-wrap items-center gap-3 rounded-xl border p-3',
                    'border-primary-500 ring-1 ring-primary-500' => $this->sourceKey === 'env',
                    'border-gray-200 dark:border-white/10' => $this->sourceKey !== 'env',
                ])>
                    <button type="button" wire:click="selectSource('env')" @disabled($envError !== null) class="flex min-w-0 flex-1 items-center gap-3 text-start disabled:cursor-not-allowed disabled:opacity-60">
                        <span @class(['flex h-4 w-4 shrink-0 items-center justify-center rounded-full border', 'border-primary-600' => $this->sourceKey === 'env', 'border-gray-300 dark:border-white/30' => $this->sourceKey !== 'env'])>
                            @if ($this->sourceKey === 'env') <span class="h-2 w-2 rounded-full bg-primary-600"></span> @endif
                        </span>
                        <span class="min-w-0">
                            <span class="block text-sm font-medium">اتصال مستقیم به MySQL (تنظیمات <span dir="ltr">WP_DB_*</span> در .env)</span>
                            <span class="block truncate text-xs text-gray-500 dark:text-gray-400" dir="ltr">{{ $envError ? \Illuminate\Support\Str::limit($envError, 140) : config('modules.wordpressimport.connection.host').' / '.config('modules.wordpressimport.connection.database') }}</span>
                        </span>
                    </button>
                    <x-filament::badge :color="$envError ? 'gray' : 'success'">{{ $envError ? 'وصل نیست' : 'متصل' }}</x-filament::badge>
                    <x-filament::icon-button icon="heroicon-m-arrow-path" color="gray" size="sm" label="بررسی دوباره" wire:click="refreshConnection" />
                </div>
            </div>

            @if ($loading)
                <div wire:key="loader-{{ $source->id }}" x-data="{ async go() { while (true) { const r = await $wire.loadTick(); if (! r || r.status !== 'loading' && r.status !== 'uploaded') break; } } }" x-init="go()"
                    class="mt-4 grid gap-1">
                    <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                        <div class="h-full rounded-full bg-warning-500 transition-all" style="width: {{ $source->progress() }}%"></div>
                    </div>
                    <div class="text-xs text-gray-600 dark:text-gray-300">
                        در حال خواندن «{{ $source->name }}»… {{ PersianText::toPersianDigits($source->progress()) }}٪ — این صفحه را باز نگه دارید.
                        @if ($source->state['tables'] ?? null) جدول‌های خوانده‌شده: {{ $fa(count($source->state['tables'])) }} @endif
                    </div>
                </div>
            @endif
        </x-filament::section>

        @if ($detected)
            {{-- 2. what was found --}}
            <x-filament::section>
                <x-slot name="heading">۲. آنچه پیدا شد</x-slot>
                <x-slot name="description">{{ $detected['site']['name'] ?: 'سایت وردپرس' }} — <span dir="ltr">{{ $detected['site']['home'] }}</span></x-slot>
                <div class="mb-4 flex flex-wrap gap-2">
                    @if ($detected['site']['woocommerce']) <x-filament::badge color="gray">ووکامرس {{ PersianText::toPersianDigits($detected['site']['woocommerce']) }}</x-filament::badge> @endif
                    <x-filament::badge color="info">سفارش‌ها: {{ $detected['hpos'] ? 'HPOS' : 'ذخیره‌سازی کلاسیک' }}</x-filament::badge>
                    <x-filament::badge color="gray">واحد پول: <span dir="ltr">{{ $detected['currency'] }}</span></x-filament::badge>
                    <x-filament::badge color="gray">پیشوند جدول‌ها: <span dir="ltr">{{ $detected['site']['prefix'] }}</span></x-filament::badge>
                    @if ($detected['plugins']['digits']) <x-filament::badge color="success">Digits (ورود با موبایل)</x-filament::badge> @endif
                    @if ($detected['plugins']['rank_math']) <x-filament::badge color="success">Rank Math</x-filament::badge> @endif
                    @if ($detected['plugins']['yoast']) <x-filament::badge color="success">Yoast</x-filament::badge> @endif
                </div>
                @if ($source && count($source->state['prefixes'] ?? []) > 1)
                    <div class="mb-4 flex flex-wrap items-center gap-2 rounded-lg bg-warning-50 p-3 text-sm dark:bg-warning-500/10">
                        <span>این فایل چند نصب وردپرس دارد. کدام؟</span>
                        @foreach ($source->state['prefixes'] as $prefix => $posts)
                            <x-filament::button size="xs" :color="$prefix === $source->prefix ? 'primary' : 'gray'" wire:click="setPrefix(@js($prefix))">
                                <span dir="ltr">{{ $prefix }}</span> ({{ $fa($posts) }} پست)
                            </x-filament::button>
                        @endforeach
                    </div>
                @endif
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-5">
                    @foreach ($detected['counts'] as $key => $n)
                        <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                            <div class="text-lg font-bold tabular-nums">{{ $fa($n) }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $countNames[$key] ?? $key }}</div>
                        </div>
                    @endforeach
                </div>
                @if (trim($detected['permalinks']['category_base'], '/') !== trim(config('khaneh.urls.category_prefix'), '/'))
                    <div class="mt-4 rounded-lg bg-warning-50 p-3 text-sm text-warning-800 dark:bg-warning-500/10 dark:text-warning-300">
                        پیشوند دسته‌ها در وردپرس «<span dir="ltr">{{ $detected['permalinks']['category_base'] }}</span>» است ولی این‌جا «<span dir="ltr">{{ config('khaneh.urls.category_prefix') }}</span>».
                        برای حفظ دقیق آدرس دسته‌ها در .env بگذارید: <code dir="ltr">KHANEH_CATEGORY_PREFIX={{ $detected['permalinks']['category_base'] }}</code> (ریدایرکت هم ساخته می‌شود).
                    </div>
                @endif
            </x-filament::section>

            {{-- 3. settings --}}
            <x-filament::section>
                <x-slot name="heading">۳. تنظیمات درون‌ریزی</x-slot>
                <x-slot name="description">از روی پایگاه داده پر شده‌اند؛ هر کدام را لازم است عوض کنید. تغییرها خودکار ذخیره می‌شوند.</x-slot>
                <x-slot name="afterHeader">
                    <x-filament::button size="sm" color="gray" wire:click="resetSettings" icon="heroicon-m-arrow-uturn-right">برگرداندن به پیش‌فرض</x-filament::button>
                </x-slot>

                <div class="grid gap-4 md:grid-cols-2">
                    <label class="grid gap-1 text-sm">
                        <span class="font-medium">آدرس سایت قدیم</span>
                        <input type="url" dir="ltr" wire:model.blur="settings.legacy_base_url" class="{{ $input }}">
                        <span class="text-xs text-gray-500 dark:text-gray-400">تصاویر از این‌جا دانلود و لینک‌های داخل متن‌ها بازنویسی می‌شوند.</span>
                    </label>
                    <label class="grid gap-1 text-sm">
                        <span class="font-medium">واحد پول ووکامرس</span>
                        <select wire:model.change="settings.currency" class="{{ $input }}">
                            @foreach (SourceAnalyzer::CURRENCIES as $code => $label)
                                <option value="{{ $code }}">{{ $code }} — {{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="text-xs text-gray-500 dark:text-gray-400">قیمت‌ها این‌جا به ریال ذخیره می‌شوند.</span>
                    </label>
                    <label class="grid gap-1 text-sm">
                        <span class="font-medium">موجودی محصولاتی که انبارشان مدیریت نمی‌شد</span>
                        <input type="number" min="0" wire:model.blur="settings.unmanaged_stock" class="{{ $input }}">
                        <span class="text-xs text-gray-500 dark:text-gray-400">برای «موجود»ها؛ «ناموجود»ها صفر می‌شوند.</span>
                    </label>
                    <label class="grid gap-1 text-sm">
                        <span class="font-medium">نظرهای بدون ستاره</span>
                        <select wire:model.change="settings.review_default_rating" class="{{ $input }}">
                            <option value="">منتقل نشوند</option>
                            @foreach ([5, 4, 3] as $r)
                                <option value="{{ $r }}">با امتیاز {{ PersianText::toPersianDigits($r) }} منتقل شوند</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                {{-- brands --}}
                <div class="mt-6">
                    <div class="mb-2 flex flex-wrap items-center gap-3">
                        <span class="text-sm font-medium">برندها</span>
                        <select wire:model.live="settings.brand_taxonomy" class="{{ $field }} max-w-full">
                            @foreach ($detected['brand_taxonomies'] as $taxonomy => $n)
                                <option value="{{ $taxonomy }}">{{ $taxonomy }} ({{ $fa($n) }} برند)</option>
                            @endforeach
                            <option value="">برند ندارد — همه زیر «{{ config('modules.wordpressimport.default_brand.name_fa') }}»</option>
                        </select>
                    </div>
                    @php($brands = $detected['brands'][$this->settings['brand_taxonomy'] ?? ''] ?? [])
                    @if ($brands)
                        <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">نامک انگلیسی هر برند در آدرس محصولات می‌آید: <span dir="ltr">/p/{برند}-{کد کالا}</span>. فقط حروف انگلیسی، عدد و خط تیره.</p>
                        <div class="max-h-80 overflow-y-auto rounded-lg border border-gray-200 dark:border-white/10">
                            <table class="w-full text-sm">
                                <thead class="sticky top-0 bg-gray-50 text-gray-500 dark:bg-gray-900 dark:text-gray-400">
                                    <tr><th class="px-3 py-2 text-start font-medium">برند</th><th class="px-3 py-2 text-start font-medium">محصول</th><th class="px-3 py-2 text-start font-medium">نامک قدیم</th><th class="px-3 py-2 text-start font-medium">نامک انگلیسی</th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($brands as $termId => $brand)
                                        <tr class="border-t border-gray-100 dark:border-white/5" wire:key="brand-{{ $termId }}">
                                            <td class="px-3 py-1.5">{{ $brand['name'] }}</td>
                                            <td class="px-3 py-1.5 tabular-nums">{{ $fa($brand['products']) }}</td>
                                            <td class="px-3 py-1.5 font-mono text-xs text-gray-500">{{ $brand['slug'] }}</td>
                                            <td class="px-3 py-1.5"><input type="text" dir="ltr" wire:model.blur="settings.brand_slugs.{{ $termId }}" class="{{ $field }} w-56 max-w-full font-mono"></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- order statuses --}}
                @if ($detected['order_statuses'])
                    <div class="mt-6">
                        <div class="mb-2 text-sm font-medium">وضعیت سفارش‌ها</div>
                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($detected['order_statuses'] as $status => $n)
                                <label class="flex items-center gap-2 rounded-lg border border-gray-200 p-2 text-sm dark:border-white/10" wire:key="st-{{ $status }}">
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate font-mono text-xs" dir="ltr">{{ $status }}</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $fa($n) }} سفارش</span>
                                    </span>
                                    <span class="text-gray-400">←</span>
                                    <select wire:model.change="settings.order_statuses.{{ $status }}" class="{{ $field }} w-40 shrink-0">
                                        @foreach (Order::STATUSES as $ours => $label)
                                            <option value="{{ $ours }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif
            </x-filament::section>

            {{-- 4. steps --}}
            <x-filament::section>
                <x-slot name="heading">۴. انتخاب و اجرا</x-slot>
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
                        <input type="number" min="1" wire:model="limit" placeholder="مثلاً ۲۰" class="{{ $field }} w-40">
                    </label>
                    <x-filament::button wire:click="start" icon="heroicon-m-play" :disabled="(bool) $run?->isActive()">شروع</x-filament::button>
                    <x-filament::button wire:click="start(true)" color="gray" :disabled="(bool) $run?->isActive()" wire:loading.attr="disabled"
                        tooltip="بدون صف، در همین صفحه اجرا می‌شود؛ تا تمام شود صبر کنید">اجرا همین‌جا</x-filament::button>
                </div>
                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                    «شروع» روی صف اجرا می‌شود (queue worker لازم است). برای درون‌ریزی کامل از ترمینال هم می‌شود:
                    <code dir="ltr" class="rounded bg-gray-100 px-1 dark:bg-white/10">php artisan wp:import{{ $source ? ' --source='.$source->id : '' }}</code>
                </p>
            </x-filament::section>
        @endif

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
                @if ($this->waitingForWorker())
                    <div class="mb-3 flex flex-wrap items-center gap-3 rounded-lg bg-warning-50 p-3 text-sm text-warning-800 dark:bg-warning-500/10 dark:text-warning-300">
                        <span>هنوز شروع نشده؛ به نظر می‌رسد queue worker روشن نیست (<code dir="ltr">php artisan queue:work</code>).</span>
                        <x-filament::button size="sm" color="warning" wire:click="runHere" wire:loading.attr="disabled">اجرا همین‌جا</x-filament::button>
                    </div>
                @endif
                <div wire:loading.flex wire:target="runHere,start" class="mb-3 items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                    <x-filament::loading-indicator class="h-4 w-4" /> در حال درون‌ریزی… صفحه را نبندید.
                </div>
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
                        <div class="text-gray-400">{{ $run->isActive() ? 'در انتظار شروع…' : 'لاگی نیست.' }}</div>
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
