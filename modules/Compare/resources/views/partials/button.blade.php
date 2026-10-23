{{-- product.buybox hook → «افزودن به مقایسه» (list kept in the browser) | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php($item = ['id' => $product->id, 'name' => $product->name, 'img' => $product->media->first()?->urlFor(160), 'url' => $product->url()])
<div x-data class="mt-3">
    <button type="button" @click="$store.compare.toggle(@js($item))"
            class="inline-flex items-center gap-2 text-sm font-bold transition"
            :class="$store.compare.has({{ $product->id }}) ? 'text-rose' : 'text-muted hover:text-rose'"
            :aria-pressed="$store.compare.has({{ $product->id }})">
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v16M5 20h14M7 8l-4 7a4 4 0 0 0 8 0L7 8Zm10 0-4 7a4 4 0 0 0 8 0l-4-7ZM5 8h14"/></svg>
        <span x-text="$store.compare.has({{ $product->id }}) ? 'در فهرست مقایسه' : 'افزودن به مقایسه'">افزودن به مقایسه</span>
        <a x-show="$store.compare.has({{ $product->id }}) && $store.compare.items.length > 1" x-cloak :href="$store.compare.link()" @click.stop
           class="text-xs font-normal text-subtle underline hover:text-rose">— دیدن مقایسه</a>
    </button>
</div>
