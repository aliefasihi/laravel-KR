{{-- Admin: bulk price change — form, live preview, recent changes | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    use App\Core\Support\{Jalali, Money, PersianText};
    $preview = $this->preview();
    $fa = fn ($n) => PersianText::toPersianDigits(number_format((int) $n));
    $toman = fn ($v) => $v === null ? '—' : Money::toman((int) $v);
@endphp
<x-filament-panels::page>
    {{ $this->form }}

    <x-filament::section icon="heroicon-o-eye" heading="پیش‌نمایش">
        <x-slot name="description">
            @if ($preview['models'])
                قیمت <b>{{ $fa($preview['models']) }}</b> مدل از <b>{{ $fa($preview['products']) }}</b> محصول عوض می‌شود.
                @if ($preview['removed_sales'])
                    <span class="text-warning-600">{{ $fa($preview['removed_sales']) }} حراج برداشته می‌شود (قیمت حراجشان دیگر کمتر از قیمت اصلی نیست).</span>
                @endif
            @else
                با این فیلترها و مقدار، قیمتی عوض نمی‌شود. مقدار تغییر را وارد کنید.
            @endif
        </x-slot>

        @if ($preview['rows']->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-start text-gray-500 dark:border-white/10">
                            <th class="py-2 text-start font-medium">محصول</th>
                            <th class="py-2 text-start font-medium">قیمت اصلی (تومان)</th>
                            <th class="py-2 text-start font-medium">قیمت حراج (تومان)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($preview['rows'] as $row)
                            <tr class="border-b border-gray-100 dark:border-white/5">
                                <td class="py-2">
                                    {{ \Illuminate\Support\Str::limit($row['variant']->product?->name, 60) }}
                                    @if ($row['variant']->options)<span class="text-xs text-gray-500"> — {{ implode('، ', (array) $row['variant']->options) }}</span>@endif
                                </td>
                                <td class="py-2 whitespace-nowrap">
                                    <span class="text-gray-400 line-through">{{ $toman($row['variant']->price) }}</span>
                                    ← <b>{{ $toman($row['price']) }}</b>
                                </td>
                                <td class="py-2 whitespace-nowrap">
                                    @if ($row['variant']->sale_price === $row['sale'])
                                        <span class="text-gray-500">{{ $toman($row['sale']) }}</span>
                                    @elseif ($row['variant']->sale_price !== null || $row['sale'] !== null)
                                        <span class="text-gray-400 line-through">{{ $toman($row['variant']->sale_price) }}</span>
                                        ← <b @class(['text-warning-600' => $row['sale'] === null])>{{ $row['sale'] === null ? 'بدون حراج' : $toman($row['sale']) }}</b>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if ($preview['models'] > $preview['rows']->count())
                    <p class="mt-2 text-xs text-gray-500">{{ $fa($preview['rows']->count()) }} ردیف اول نشان داده شد.</p>
                @endif
            </div>
        @endif

        <div class="mt-4">{{ $this->applyAction }}</div>
    </x-filament::section>

    @php($history = $this->history())
    @if ($history->isNotEmpty())
        <x-filament::section icon="heroicon-o-clock" heading="تغییرهای اخیر" collapsible>
            <ul class="divide-y divide-gray-100 text-sm dark:divide-white/5">
                @foreach ($history as $change)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-2.5">
                        <div>
                            <b>{{ $change->summary }}</b>
                            <div class="text-xs text-gray-500">
                                {{ Jalali::dateTime($change->created_at) }} · {{ $fa($change->variants_count) }} مدل
                                @if ($change->user) · {{ $change->user->name }} @endif
                            </div>
                        </div>
                        @if ($change->undone_at)
                            <span class="text-xs text-gray-500">برگردانده شد ({{ Jalali::format($change->undone_at) }})</span>
                        @else
                            {{ ($this->undoAction)(['change' => $change->id]) }}
                        @endif
                    </li>
                @endforeach
            </ul>
        </x-filament::section>
    @endif
</x-filament-panels::page>
