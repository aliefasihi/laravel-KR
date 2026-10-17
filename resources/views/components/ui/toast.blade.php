{{-- Flash message toast | نویسنده: علی فصیحی (Ali Fasihi) --}}
@if (session('status'))
    <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show" x-transition.opacity.duration.300ms
         class="fixed inset-x-4 bottom-28 z-[70] mx-auto max-w-md lg:bottom-8" role="status">
        <div class="glass-strong flex items-center gap-3 rounded-2xl px-5 py-4 text-sm font-medium text-navy">
            <span class="grid size-8 shrink-0 place-items-center rounded-full bg-rose text-white">
                <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.6l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
            </span>
            <span class="flex-1">{{ session('status') }}</span>
            <button type="button" @click="show = false" class="text-subtle hover:text-navy" aria-label="بستن">✕</button>
        </div>
    </div>
@endif
