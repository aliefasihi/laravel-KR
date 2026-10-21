{{-- «Add to my wedding list» under the buy box | نویسنده: علی فصیحی (Ali Fasihi) --}}
<div class="mt-2 text-sm">
    @if ($registry)
        @if ($added)
            <a href="{{ route('customer.registry') }}" class="inline-flex items-center gap-2 font-bold text-ok">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><path d="M20 12v8a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-8M3 8h18v4H3zM12 21V8M12 8S10.5 3 8 4s0 4 4 4ZM12 8s1.5-5 4-4 0 4-4 4Z"/></svg>
                در «{{ $registry->title }}» است — مدیریت لیست
            </a>
        @else
            <button type="button" wire:click="add" wire:loading.attr="disabled" class="inline-flex items-center gap-2 font-bold text-muted transition hover:text-rose">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><path d="M20 12v8a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-8M3 8h18v4H3zM12 21V8M12 8S10.5 3 8 4s0 4 4 4ZM12 8s1.5-5 4-4 0 4-4 4Z"/></svg>
                افزودن به «{{ $registry->title }}»
            </button>
        @endif
    @else
        <a href="{{ $loggedIn ? route('customer.registry') : route('customer.login', ['back' => '/account/registry']) }}" class="inline-flex items-center gap-2 font-bold text-muted transition hover:text-rose">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><path d="M20 12v8a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-8M3 8h18v4H3zM12 21V8M12 8S10.5 3 8 4s0 4 4 4ZM12 8s1.5-5 4-4 0 4-4 4Z"/></svg>
            لیست جهیزیه بسازید و برای فامیل بفرستید
        </a>
    @endif
</div>
