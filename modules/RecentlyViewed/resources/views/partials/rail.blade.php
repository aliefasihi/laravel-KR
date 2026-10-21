{{-- «Recently viewed» row (fetched by the browser) | نویسنده: علی فصیحی (Ali Fasihi) --}}
<section aria-labelledby="recent-title" class="space-y-6">
    <div class="flex items-end justify-between gap-4">
        <h2 id="recent-title" class="font-display text-2xl font-black text-navy">بازدیدهای اخیر شما</h2>
        <button type="button" class="text-xs font-bold text-muted hover:text-rose" @click="clear()">پاک کردن</button>
    </div>
    <ul class="rail !gap-4">
        @foreach ($products as $product)
            <li class="w-[46%] shrink-0 sm:w-[30%] md:w-[23%] lg:w-[18.5%]"><x-product.card :product="$product" /></li>
        @endforeach
    </ul>
</section>
