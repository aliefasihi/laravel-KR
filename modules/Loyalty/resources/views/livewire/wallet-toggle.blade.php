{{-- Cart/checkout: pay with the wallet | نویسنده: علی فصیحی (Ali Fasihi) --}}
<div>
    @if ($balance > 0)
        <label class="flex cursor-pointer items-center gap-3 rounded-2xl bg-cream px-4 py-3 text-sm">
            <input type="checkbox" wire:model.live="use" class="size-[18px] shrink-0 rounded-md accent-rose">
            <span class="min-w-0 flex-1">
                <span class="block font-bold text-navy">پرداخت با کیف پول</span>
                <span class="text-xs text-muted">موجودی: {{ \App\Core\Support\Money::toman($balance) }} تومان</span>
            </span>
            <svg class="size-6 shrink-0 text-caramel-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" aria-hidden="true"><path d="M3 7.5A2.5 2.5 0 0 1 5.5 5H18v3M3 7.5V18a2 2 0 0 0 2 2h14a1 1 0 0 0 1-1v-3M3 7.5A2.5 2.5 0 0 0 5.5 10H20v6h-4a2 2 0 0 1 0-4h4"/></svg>
        </label>
    @endif
</div>
