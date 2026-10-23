{{-- Floating compare bar: the products picked for comparison (browser only) | نویسنده: علی فصیحی (Ali Fasihi) --}}
@unless (request()->routeIs('compare') || View::hasSection('bare'))
    <div x-data x-show="$store.compare.items.length" x-cloak x-transition.opacity
         class="glass-strong frost fixed inset-x-0 bottom-[5.75rem] z-40 mx-auto flex w-max max-w-[calc(100%-8.5rem)] items-center gap-2 rounded-full p-1.5 shadow-[0_18px_40px_-18px_rgb(0_0_0/0.45)] lg:bottom-6"
         role="region" aria-label="فهرست مقایسه">
        <div class="flex -space-x-2 ps-1 rtl:space-x-reverse">
            <template x-for="item in $store.compare.items" :key="item.id">
                <span class="relative">
                    <img :src="item.img || '/favicon.ico'" :alt="item.name" class="size-9 rounded-full border-2 border-surface bg-cream object-cover">
                </span>
            </template>
        </div>
        <a :href="$store.compare.link()" class="btn-navy !px-4 !py-2 text-xs"
           :class="$store.compare.items.length < 2 && 'pointer-events-none opacity-60'">
            مقایسه
            <span x-text="'(' + toFa($store.compare.items.length) + ')'"></span>
        </a>
        <button type="button" @click="$store.compare.clear()" class="grid size-8 place-items-center rounded-full text-muted hover:bg-navy/5" aria-label="خالی کردن فهرست مقایسه">✕</button>
    </div>
@endunless
