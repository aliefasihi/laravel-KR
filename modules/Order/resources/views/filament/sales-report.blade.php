{{-- Admin sales report | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    use App\Core\Support\{Money, PersianText};
    use Modules\Order\Models\Order;
    $fa = fn ($n) => PersianText::toPersianDigits(number_format((int) $n));
    $toman = fn ($v) => Money::toman((int) $v);
    $delta = function (string $key) use ($kpis, $previous) {
        $before = (int) $previous[$key];
        if ($before === 0) {
            return null;
        }
        return (int) round(((int) $kpis[$key] - $before) * 100 / $before);
    };
    $cards = [
        ['revenue', 'درآمد کل', true, 'مبالغ دریافتی منهای استرداد'],
        ['net', 'فروش خالص', true, 'کالاها − تخفیف − استرداد'],
        ['orders', 'سفارش', false, null],
        ['average', 'میانگین هر سفارش', true, null],
        ['items', 'کالای فروخته‌شده', false, null],
        ['discounts', 'تخفیف کوپن', true, null],
        ['shipping', 'هزینه‌ی ارسال دریافتی', true, null],
        ['new_customers', 'مشتری تازه', false, null],
        ['returning', 'سفارش مشتری‌های قدیمی', false, 'سفارش‌هایی که خریدارشان قبل از این بازه هم خرید کرده بود'],
        ['cancelled', 'لغوشده', false, null],
    ];
@endphp
<x-filament-panels::page>
    {{-- range --}}
    <div class="flex flex-wrap items-center gap-2">
        @foreach ($this->presets() as $key => $label)
            <x-filament::button size="sm" :color="$preset === $key ? 'primary' : 'gray'" :outlined="$preset !== $key" wire:click="applyPreset('{{ $key }}')">{{ $label }}</x-filament::button>
        @endforeach
        <div class="flex items-center gap-2 ms-auto text-sm">
            <span class="text-gray-500">از</span>
            <input type="date" wire:model.live="from" class="rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-white/5" dir="ltr">
            <span class="text-gray-500">تا</span>
            <input type="date" wire:model.live="to" class="rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-white/5" dir="ltr">
        </div>
    </div>
    <p class="-mt-2 text-sm text-gray-500">{{ $rangeLabel }} · مقایسه با دوره‌ی هم‌اندازه‌ی قبل</p>

    {{-- KPIs --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
        @foreach ($cards as [$key, $label, $money, $hint])
            @php($d = $delta($key))
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-xs text-gray-500" @if ($hint) title="{{ $hint }}" @endif>{{ $label }}</p>
                <p class="mt-1 text-xl font-bold text-gray-950 dark:text-white">{{ $money ? $toman($kpis[$key]) : $fa($kpis[$key]) }}@if ($money)<span class="text-xs font-normal text-gray-400"> تومان</span>@endif</p>
                @if ($d !== null)
                    <p @class(['mt-1 text-xs font-semibold', 'text-success-600' => $d >= 0, 'text-danger-600' => $d < 0])>
                        {{ $d >= 0 ? '▲' : '▼' }} {{ PersianText::toPersianDigits(abs($d)) }}٪
                    </p>
                @else
                    <p class="mt-1 text-xs text-gray-400">—</p>
                @endif
            </div>
        @endforeach
    </div>

    {{-- chart --}}
    @livewire(\Modules\Order\Filament\Widgets\SalesChart::class, ['from' => $from, 'to' => $to], key('chart-'.$from.'-'.$to))

    {{-- results of the selling tools (abandoned-cart SMS, wedding lists, gift wrap…) --}}
    @if ($tools !== [])
        <x-filament::section heading="نتیجه‌ی ابزارهای فروش در این بازه" compact>
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                @foreach ($tools as $card)
                    <div class="rounded-lg p-3 ring-1 ring-gray-950/5 dark:ring-white/10">
                        <p class="text-xs text-gray-500">{{ $card['label'] }}</p>
                        <p class="mt-1 text-lg font-bold">{{ $card['value'] }}</p>
                        @if ($card['hint'])<p class="mt-0.5 text-xs text-gray-400">{{ $card['hint'] }}</p>@endif
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif

    {{-- tables --}}
    <div class="grid gap-6 lg:grid-cols-2">
        @foreach ([
            ['پرفروش‌ترین محصولات', $products, 'name', 'quantity', 'تعداد'],
            ['کانال‌های فروش', $channels, 'name', 'orders', 'سفارش'],
            ['دسته‌ها', $categories, 'name', 'quantity', 'تعداد'],
            ['برندها', $brands, 'name', 'quantity', 'تعداد'],
            ['استان‌ها', $provinces, 'name', 'orders', 'سفارش'],
            ['روش‌های پرداخت', $payments, 'name', 'orders', 'سفارش'],
            ['روش‌های ارسال', $shipping, 'name', 'orders', 'سفارش'],
            ['کدهای تخفیف', $coupons, 'name', 'orders', 'سفارش'],
        ] as [$title, $rows, $nameKey, $countKey, $countLabel])
            <x-filament::section :heading="$title" compact>
                @if ($rows->isEmpty())
                    <p class="text-sm text-gray-400">در این بازه داده‌ای نیست.</p>
                @else
                    @php($max = max(1, (int) $rows->max('revenue')))
                    <table class="w-full text-sm">
                        <thead><tr class="text-xs text-gray-500"><th class="pb-2 text-start font-medium">نام</th><th class="pb-2 text-center font-medium">{{ $countLabel }}</th><th class="pb-2 text-end font-medium">{{ $title === 'کدهای تخفیف' ? 'تخفیف' : 'مبلغ' }} (تومان)</th></tr></thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr class="border-t border-gray-100 dark:border-white/5">
                                    <td class="py-2">
                                        <span class="block truncate">{{ $row->{$nameKey} ?: '—' }}</span>
                                        <span class="mt-1 block h-1 rounded-full bg-primary-500/70" style="width: {{ max(2, (int) round(((int) $row->revenue) * 100 / $max)) }}%"></span>
                                    </td>
                                    <td class="py-2 text-center">{{ $fa($row->{$countKey}) }}</td>
                                    <td class="py-2 text-end font-semibold">{{ $toman($title === 'کدهای تخفیف' ? $row->discount : $row->revenue) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </x-filament::section>
        @endforeach
    </div>

    <x-filament::section heading="وضعیت سفارش‌های ثبت‌شده در این بازه" compact>
        <div class="flex flex-wrap gap-3">
            @foreach (Order::STATUSES as $status => $label)
                <a href="{{ \Modules\Order\Filament\Resources\Orders\OrderResource::getUrl('index', ['activeTab' => $status]) }}" class="flex items-center gap-2 rounded-lg px-3 py-2 ring-1 ring-gray-950/5 hover:bg-gray-50 dark:ring-white/10 dark:hover:bg-white/5">
                    <x-filament::badge :color="Order::COLORS[$status]">{{ $label }}</x-filament::badge>
                    <span class="font-bold">{{ $fa($statuses[$status] ?? 0) }}</span>
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-panels::page>
