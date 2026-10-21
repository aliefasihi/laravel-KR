{{-- Sets with a discount + «often bought together» on the product page | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    use App\Core\Support\Money;
    use App\Core\Support\PersianText;
    use Modules\Bundles\Support\Bundles;
    $plus = '<span class="grid size-7 shrink-0 place-items-center self-center rounded-full bg-navy/5 text-sm font-black text-muted" aria-hidden="true">+</span>';
@endphp
<div @class(['space-y-6', 'hidden' => $bundles->isEmpty() && $together->isEmpty()])>
    @if ($notice)
        <p class="glass rounded-2xl px-5 py-3 text-sm font-bold text-rose" role="status">{{ $notice }}</p>
    @endif

    {{-- Sets --}}
    @foreach ($bundles as $bundle)
        @php([$full, $price] = Bundles::prices($bundle))
        <section class="glass rounded-[var(--radius-glass)] p-5 sm:p-7" aria-labelledby="bundle-{{ $bundle->id }}" wire:key="bundle-{{ $bundle->id }}">
            <div class="flex flex-wrap items-center gap-3">
                <h2 id="bundle-{{ $bundle->id }}" class="text-lg font-black text-navy">ست پیشنهادی: {{ $bundle->title }}</h2>
                @if ($bundle->discount_percent > 0)
                    <span class="badge-off">{{ PersianText::toPersianDigits($bundle->discount_percent) }}٪ تخفیف ست</span>
                @endif
            </div>
            @if ($bundle->description)
                <p class="mt-1 text-sm text-muted">{{ $bundle->description }}</p>
            @endif
            <div class="mt-5 grid items-center gap-6 lg:grid-cols-[1fr_16rem]">
                <ul class="flex flex-wrap items-stretch gap-2">
                    @foreach ($bundle->items as $item)
                        @unless ($loop->first) {!! $plus !!} @endunless
                        <li class="w-28 sm:w-32">
                            <a href="{{ $item->product->url() }}" @class(['block overflow-hidden rounded-2xl bg-cream ring-2', 'ring-rose' => $item->product_id === $product->id, 'ring-transparent' => $item->product_id !== $product->id])>
                                <x-media.picture :media="$item->product->media->first()" :alt="$item->product->name" sizes="128px" class="aspect-square w-full object-cover" />
                            </a>
                            <p class="mt-2 line-clamp-2 text-xs leading-5 text-navy">
                                @if ($item->quantity > 1)<span class="font-black text-rose">{{ PersianText::toPersianDigits($item->quantity) }}×</span>@endif
                                {{ $item->product->name }}
                            </p>
                        </li>
                    @endforeach
                </ul>
                <div class="rounded-2xl bg-cream/70 p-4 text-center">
                    @if ($price < $full)
                        <del class="text-sm text-subtle">{{ Money::toman($full) }}</del>
                    @endif
                    <p class="text-2xl font-black text-navy">{{ Money::toman($price) }} <span class="text-sm font-medium text-muted">تومان</span></p>
                    @if ($price < $full)
                        <p class="mt-1 text-xs font-bold text-ok">{{ Money::toman($full - $price) }} تومان سود شما</p>
                    @endif
                    <button type="button" wire:click="addBundle({{ $bundle->id }})" wire:loading.attr="disabled" class="btn-rose mt-3 w-full">افزودن ست به سبد</button>
                </div>
            </div>
        </section>
    @endforeach

    {{-- Bought together --}}
    @if ($together->isNotEmpty())
        @php($all = collect([$product])->merge($together))
        <section class="glass rounded-[var(--radius-glass)] p-5 sm:p-7" aria-labelledby="together-title"
                 x-data="{ picked: @entangle('picked'), prices: @js($together->mapWithKeys(fn ($p) => [$p->id => Bundles::offer($p)?->currentPrice() ?? 0])), own: {{ Bundles::offer($product)?->currentPrice() ?? 0 }} }">
            <h2 id="together-title" class="text-lg font-black text-navy">معمولاً با هم خریده می‌شوند</h2>
            <div class="mt-5 grid items-center gap-6 lg:grid-cols-[1fr_16rem]">
                <ul class="flex flex-wrap items-stretch gap-2">
                    @foreach ($all as $item)
                        @unless ($loop->first) {!! $plus !!} @endunless
                        <li class="w-28 sm:w-32" wire:key="tg-{{ $item->id }}">
                            <label class="block cursor-pointer">
                                <span class="relative block overflow-hidden rounded-2xl bg-cream transition" @unless ($loop->first) :class="picked.includes({{ $item->id }}) ? '' : 'opacity-40'" @endunless>
                                    <x-media.picture :media="$item->media->first()" :alt="$item->name" sizes="128px" class="aspect-square w-full object-cover" />
                                    @unless ($loop->first)
                                        <input type="checkbox" value="{{ $item->id }}" x-model.number="picked" class="absolute top-2 start-2 size-5 accent-rose" aria-label="انتخاب {{ $item->name }}">
                                    @endunless
                                </span>
                                <span class="mt-2 line-clamp-2 block text-xs leading-5 text-navy">{{ $loop->first ? 'همین محصول: ' : '' }}{{ $item->name }}</span>
                                <span class="block text-xs font-bold text-muted">{{ Money::toman(Bundles::offer($item)?->currentPrice() ?? 0) }} تومان</span>
                            </label>
                        </li>
                    @endforeach
                </ul>
                <div class="rounded-2xl bg-cream/70 p-4 text-center">
                    <p class="text-xs text-muted">جمع <span x-text="toFa(picked.length + 1)"></span> کالا</p>
                    <p class="text-2xl font-black text-navy"><span x-text="toFa(Math.round((own + picked.reduce((s, id) => s + (prices[id] ?? 0), 0)) / 10).toLocaleString('en-US'))"></span> <span class="text-sm font-medium text-muted">تومان</span></p>
                    <button type="button" wire:click="addTogether" wire:loading.attr="disabled" class="btn-navy mt-3 w-full">افزودن همه به سبد</button>
                </div>
            </div>
        </section>
    @endif
</div>
