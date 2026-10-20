{{-- Product filters (inside the listing's <form>): sort, toggles, price, brands, category attributes.
     Shared by every listing design; uses the <x-listing> component's variables.
     $drawer = true: the filters live in a drawer on every screen (sorting is in the toolbar, one «show» button). | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    use App\Core\Support\Money;
    use App\Core\Support\PersianText;
    $drawer ??= false;
@endphp
@foreach ($hidden as $name => $value)
    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
@endforeach
<div class="flex items-center justify-between">
    <p class="text-base font-black text-navy">فیلتر محصولات</p>
    @if ($filtered)
        <a href="{{ $reset }}" class="text-xs font-bold text-rose">حذف همه</a>
    @endif
    <button type="button" @class(['btn-icon size-9 text-navy', 'lg:hidden' => ! $drawer]) @click="sheet = false" aria-label="بستن">✕</button>
</div>

<fieldset @class(['max-lg:hidden' => ! $drawer, 'hidden' => $drawer])>
    <legend class="mb-3 text-sm font-bold text-navy">مرتب‌سازی</legend>
    <div class="grid grid-cols-2 gap-1.5">
        @foreach ($sorts as $key => $label)
            <label class="cursor-pointer">
                <input type="radio" name="sort" value="{{ $key }}" class="peer sr-only" @checked($filters['sort'] === $key)>
                <span class="block rounded-xl bg-surface/60 px-3 py-2 text-center text-[13px] text-muted transition peer-checked:bg-deep peer-checked:font-bold peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-rose hover:text-navy">{{ $label }}</span>
            </label>
        @endforeach
    </div>
</fieldset>

<div class="space-y-2.5">
    @foreach (['sale' => 'فقط کالاهای تخفیف‌دار', 'in_stock' => 'فقط کالاهای موجود'] as $name => $label)
        <label class="flex cursor-pointer items-center justify-between gap-3 rounded-2xl bg-surface/60 px-4 py-3">
            <span class="text-sm font-medium text-navy">{{ $label }}</span>
            <input type="checkbox" name="{{ $name }}" value="1" class="peer sr-only" @checked($filters[$name])>
            <span class="relative h-6 w-11 shrink-0 rounded-full bg-navy/15 transition peer-checked:bg-rose peer-focus-visible:ring-2 peer-focus-visible:ring-rose/40 after:absolute after:top-0.5 after:start-0.5 after:size-5 after:rounded-full after:bg-surface after:shadow after:transition peer-checked:after:-translate-x-5" aria-hidden="true"></span>
        </label>
    @endforeach
</div>

<fieldset>
    <legend class="mb-3 text-sm font-bold text-navy">محدوده قیمت <span class="font-normal text-muted">(تومان)</span></legend>
    <div class="grid grid-cols-2 gap-2">
        <label class="sr-only" for="f-min">از</label>
        <input id="f-min" name="min" type="text" inputmode="numeric" value="{{ $filters['min'] }}" placeholder="{{ $priceRange ? 'از '.Money::toman($priceRange['min']) : 'از' }}" class="field !py-2.5 text-center" @change.stop>
        <label class="sr-only" for="f-max">تا</label>
        <input id="f-max" name="max" type="text" inputmode="numeric" value="{{ $filters['max'] }}" placeholder="{{ $priceRange ? 'تا '.Money::toman($priceRange['max']) : 'تا' }}" class="field !py-2.5 text-center" @change.stop>
    </div>
    @unless ($drawer)
        <button class="mt-2 hidden w-full rounded-xl bg-navy/5 py-2 text-xs font-bold text-navy hover:bg-deep hover:text-white lg:block">اعمال قیمت</button>
    @endunless
</fieldset>

@if ($brands->count() > 1 || $filters['brand'] !== [])
    <fieldset>
        <legend class="mb-3 text-sm font-bold text-navy">برند</legend>
        <div class="space-y-1.5">
            @foreach ($brands as $brand)
                @php($n = $counts[$brand->id] ?? null)
                <label @class(['flex cursor-pointer items-center gap-3 rounded-xl px-2 py-1.5 hover:bg-surface/60', 'opacity-50' => $n === 0])>
                    <input type="checkbox" name="brand[]" value="{{ $brand->slug }}" @checked(in_array($brand->slug, $filters['brand'], true)) class="size-[18px] rounded-md accent-rose">
                    <span class="flex-1 text-sm text-navy">{{ $brand->name_fa }}</span>
                    @if ($n !== null)
                        <span class="rounded-full bg-navy/5 px-2 py-0.5 text-[11px] font-bold text-muted">{{ PersianText::toPersianDigits($n) }}</span>
                    @else
                        <span class="font-latin text-[10px] tracking-wider text-subtle uppercase" dir="ltr">{{ $brand->name_en }}</span>
                    @endif
                </label>
            @endforeach
        </div>
    </fieldset>
@endif

{{-- category-specific filters (attributes) --}}
@foreach ($filterAttributes as $attribute)
    {{-- inline php directives only: a block one here would swallow the inline ones above it --}}
    @php($chosen = $filters['attr'][$attribute->slug] ?? [])
    @php($options = $attribute->values->filter(fn ($v) => ($valueCounts[$v->id] ?? 0) > 0 || in_array($v->id, $chosen, true))->values())
    @if ($options->isNotEmpty())
        <fieldset x-data="{ more: false }">
            <legend class="mb-3 text-sm font-bold text-navy">{{ $attribute->name }}@if ($attribute->unit) <span class="font-normal text-muted">({{ $attribute->unit }})</span>@endif</legend>
            @if ($attribute->display === 'color')
                <div class="flex flex-wrap gap-2">
                    @foreach ($options as $value)
                        <label class="group cursor-pointer" title="{{ $value->value }}">
                            <input type="checkbox" name="attr[{{ $attribute->slug }}][]" value="{{ $value->id }}" class="peer sr-only" @checked(in_array($value->id, $chosen, true))>
                            <span class="flex items-center gap-1.5 rounded-full bg-surface/60 py-1 ps-1 pe-3 text-xs text-navy ring-1 ring-line transition peer-checked:bg-surface peer-checked:ring-2 peer-checked:ring-rose peer-focus-visible:ring-2 peer-focus-visible:ring-rose/50">
                                <span class="size-5 rounded-full ring-1 ring-black/10" style="background: {{ $value->color ?: '#ddd' }}"></span>
                                {{ $value->value }}
                            </span>
                        </label>
                    @endforeach
                </div>
            @else
                <div class="space-y-1.5">
                    @foreach ($options as $value)
                        @php($n = $valueCounts[$value->id] ?? 0)
                        <label @class(['flex cursor-pointer items-center gap-3 rounded-xl px-2 py-1.5 hover:bg-surface/60', 'opacity-50' => $n === 0])
                               @if ($loop->index >= 6 && ! in_array($value->id, $chosen, true)) x-show="more" x-cloak @endif>
                            <input type="checkbox" name="attr[{{ $attribute->slug }}][]" value="{{ $value->id }}" @checked(in_array($value->id, $chosen, true)) class="size-[18px] rounded-md accent-rose">
                            <span class="flex-1 text-sm text-navy">{{ $attribute->format($value->value) }}</span>
                            <span class="rounded-full bg-navy/5 px-2 py-0.5 text-[11px] font-bold text-muted">{{ PersianText::toPersianDigits($n) }}</span>
                        </label>
                    @endforeach
                </div>
                @if ($options->count() > 6)
                    <button type="button" class="mt-1 px-2 text-xs font-bold text-rose" x-on:click="more = ! more" x-text="more ? 'نمایش کمتر' : 'نمایش همه ({{ PersianText::toPersianDigits($options->count()) }})'"></button>
                @endif
            @endif
        </fieldset>
    @endif
@endforeach

<button @class(['btn-navy w-full', 'lg:hidden' => ! $drawer])>نمایش {{ PersianText::toPersianDigits($products->total()) }} محصول</button>
<noscript><button class="btn-navy w-full">اعمال فیلترها</button></noscript>
