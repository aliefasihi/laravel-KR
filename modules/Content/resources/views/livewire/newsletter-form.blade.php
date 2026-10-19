{{-- Footer newsletter box | نویسنده: علی فصیحی (Ali Fasihi) --}}
<div class="grid gap-2">
    @if ($done)
        <p class="glass-strong flex items-center gap-3 rounded-full px-5 py-3 text-sm font-bold text-navy" role="status">
            <span class="grid size-7 shrink-0 place-items-center rounded-full bg-rose text-white">
                <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.6l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
            </span>
            {{ $done }}
        </p>
    @else
        <form wire:submit="subscribe" class="glass-strong flex items-center gap-2 rounded-full p-1.5 ps-5" novalidate>
            <label for="newsletter-contact" class="sr-only">ایمیل یا شماره موبایل</label>
            <input id="newsletter-contact" wire:model="contact" type="text" inputmode="email" autocomplete="email" required
                   placeholder="ایمیل یا شماره موبایل" class="min-w-0 flex-1 bg-transparent text-sm text-ink placeholder:text-subtle focus:outline-none">
            <button class="btn-rose !px-5 !py-2.5" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="subscribe">عضویت</span>
                <span wire:loading wire:target="subscribe">…</span>
            </button>
        </form>
        @error('contact')<p class="ps-5 text-sm text-rose" role="alert">{{ $message }}</p>@enderror
    @endif
</div>
