{{-- Wholesale price list + quick order | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php use App\Core\Support\{Money, PersianText}; use Modules\Wholesale\Support\Wholesale; @endphp
<div class="pb-24">
    <div class="glass mb-4 flex flex-wrap items-center gap-3 rounded-[var(--radius-glass)] p-3">
        <label class="sr-only" for="pl-search">جستجو در لیست قیمت</label>
        <input id="pl-search" type="search" wire:model.live.debounce.500ms="search" placeholder="نام یا کد کالا…" class="field !w-full sm:!w-72">
        <label class="sr-only" for="pl-cat">دسته‌بندی</label>
        <select id="pl-cat" wire:model.live="category" class="field !w-full sm:!w-60">
            <option value="0">همه‌ی دسته‌ها</option>
            @foreach ($categoryOptions as $root)
                <option value="{{ $root->id }}">{{ $root->name }}</option>
                @foreach ($root->children as $child)
                    <option value="{{ $child->id }}">— {{ $child->name }}</option>
                @endforeach
            @endforeach
        </select>
        <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-bold text-navy">
            <input type="checkbox" wire:model.live="inStock" class="size-4 accent-rose"> فقط موجودها
        </label>
        <span class="ms-auto text-xs text-muted" wire:loading.delay>در حال به‌روزرسانی…</span>
    </div>

    @if ($products->isEmpty())
        <p class="glass rounded-[var(--radius-glass)] p-10 text-center font-bold text-navy">کالایی با این مشخصات پیدا نشد.</p>
    @else
        <ul class="space-y-3">
            @foreach ($products as $product)
                @php
                    $tiers = $wholesale->tiersFor($product);
                @endphp
                <li class="glass rounded-[var(--radius-card)] p-3 sm:p-4" wire:key="pl-{{ $product->id }}">
                    <div class="flex items-center gap-3">
                        <a href="{{ $product->url() }}" class="shrink-0 overflow-hidden rounded-xl" target="_blank">
                            <x-media.picture :media="$product->media->first()" :alt="$product->name" sizes="56px" class="size-14 object-cover" />
                        </a>
                        <div class="min-w-0 flex-1">
                            <a href="{{ $product->url() }}" target="_blank" class="line-clamp-1 font-bold text-navy hover:text-rose">{{ $product->name }}</a>
                            <p class="mt-0.5 text-xs text-muted">
                                @if ($product->brand)<span class="text-caramel-600">{{ $product->brand->name_fa }}</span> · @endif
                                @if ($tiers)
                                    @foreach ($tiers as $tier)
                                        <span class="whitespace-nowrap">از {{ PersianText::toPersianDigits($tier['min_qty']) }} عدد {{ Wholesale::percent($tier['percent']) }}٪</span>@if (! $loop->last) · @endif
                                    @endforeach
                                @else
                                    قیمت همکار ندارد
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="mt-3 divide-y divide-line rounded-2xl ring-1 ring-line">
                        @foreach ($product->variants as $variant)
                            @php
                                $typed = (int) PersianText::toLatinDigits((string) ($qty[$variant->id] ?? ''));
                                $unit = $wholesale->priceFor($variant, max(1, $typed));
                                $retail = $variant->currentPrice();
                            @endphp
                            <div class="grid grid-cols-[1fr_auto] items-center gap-x-3 gap-y-1 p-2.5 text-sm sm:grid-cols-[1fr_8rem_8rem_6.5rem]" wire:key="plv-{{ $variant->id }}">
                                <div class="min-w-0">
                                    <p class="truncate font-bold text-ink">{{ $variant->options ? implode(' · ', $variant->options) : 'تک مدل' }}</p>
                                    <p class="text-xs {{ $variant->stock > 0 ? 'text-muted' : 'text-rose' }}">
                                        {{ $variant->stock > 0 ? 'موجودی: '.PersianText::toPersianDigits($variant->stock) : 'ناموجود' }}
                                        @if ($variant->sku)<span class="font-latin" dir="ltr"> · {{ $variant->sku }}</span>@endif
                                    </p>
                                </div>
                                <div class="text-end sm:text-center">
                                    <span class="block text-[11px] text-subtle">قیمت سایت</span>
                                    <span @class(['font-bold', 'text-subtle line-through decoration-rose/50' => $unit, 'text-navy' => ! $unit])>{{ Money::toman($retail) }}</span>
                                </div>
                                <div class="text-start sm:text-center">
                                    <span class="block text-[11px] text-subtle">قیمت همکار{{ $typed > 1 ? ' ('.PersianText::toPersianDigits($typed).' عدد)' : '' }}</span>
                                    @if ($unit)
                                        <span class="font-black text-ok">{{ Money::toman($unit) }}</span> <span class="text-[11px] text-muted">تومان</span>
                                    @else
                                        <span class="text-xs text-muted">{{ $tiers ? 'حراج سایت ارزان‌تر است' : '—' }}</span>
                                    @endif
                                </div>
                                <div class="text-end">
                                    @if ($variant->stock > 0)
                                        <label class="sr-only" for="plq-{{ $variant->id }}">تعداد {{ $product->name }} {{ $variant->options ? implode(' ', $variant->options) : '' }}</label>
                                        <input id="plq-{{ $variant->id }}" type="text" inputmode="numeric" dir="ltr" autocomplete="off"
                                               wire:model.live.debounce.400ms="qty.{{ $variant->id }}" placeholder="تعداد"
                                               class="field !w-24 !py-2 text-center">
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </li>
            @endforeach
        </ul>

        <div class="mt-5">{{ $products->links() }}</div>
    @endif

    <div class="fixed inset-x-0 bottom-[5.75rem] z-40 mx-auto w-[calc(100%-8.5rem)] max-w-3xl lg:bottom-6 lg:w-[calc(100%-2rem)]">
        <div class="glass-strong frost flex items-center justify-between gap-2 rounded-[var(--radius-glass)] p-2 ps-4 shadow-xl sm:gap-3">
            @php
                $count = collect($qty)->filter(fn ($q) => (int) PersianText::toLatinDigits((string) $q) > 0)->count();
            @endphp
            <p class="min-w-0 text-xs font-bold leading-6 text-navy sm:text-sm" role="status">
                @if ($message)
                    {{ $message }} <a href="{{ route('checkout.cart') }}" class="text-rose underline">دیدن سبد</a>
                @elseif ($count)
                    {{ PersianText::toPersianDigits($count) }} مدل انتخاب شده
                @else
                    <span class="sm:hidden">تعداد را بنویسید</span><span class="hidden sm:inline">تعداد هر مدل را بنویسید و یک‌جا به سبد اضافه کنید.</span>
                    @if ($minimum)<span class="hidden text-xs font-normal text-muted sm:block">قیمت همکار از جمع خرید {{ Money::toman($minimum) }} تومان.</span>@endif
                @endif
            </p>
            <button type="button" wire:click="addAll" class="btn-rose shrink-0 !px-4 sm:!px-6" @disabled(! $count) wire:loading.attr="disabled" wire:target="addAll">افزودن به سبد</button>
        </div>
    </div>
</div>
