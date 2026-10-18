{{-- Mobile drawer: روشن / تاریک / خودکار | نویسنده: علی فصیحی (Ali Fasihi) --}}
<div class="px-4 pb-3" x-data="{ mode: window.krTheme?.mode() ?? 'system' }" @theme-changed.window="mode = $event.detail.mode">
    <p class="px-1 pb-2 text-xs font-bold text-subtle">ظاهر سایت</p>
    <div class="grid grid-cols-3 gap-1 rounded-2xl bg-navy/5 p-1" role="radiogroup" aria-label="ظاهر سایت">
        @foreach (['light' => 'روشن', 'dark' => 'تاریک', 'system' => 'خودکار'] as $value => $label)
            <button type="button" role="radio" :aria-checked="mode === '{{ $value }}'"
                    class="rounded-xl py-2 text-xs font-bold transition"
                    :class="mode === '{{ $value }}' ? 'bg-surface text-rose shadow-sm' : 'text-muted'"
                    @click="krTheme.set('{{ $value }}')">{{ $label }}</button>
        @endforeach
    </div>
</div>
