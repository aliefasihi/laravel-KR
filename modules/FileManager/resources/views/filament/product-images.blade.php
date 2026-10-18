{{-- «تصاویر محصول» field | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    $statePath = $getStatePath();
    $form = $getFormStatePath();
    $record = $getRecord();
@endphp
<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="{
            state: $wire.$entangle('{{ $statePath }}'),
            thumbs: @js($getThumbnails()),
            open() {
                Livewire.dispatch('open-image-picker', {
                    target: '{{ $statePath }}',
                    brandId: $wire.get('{{ $form }}.brand_id'),
                    sku: $wire.get('{{ $form }}.sku'),
                    name: $wire.get('{{ $form }}.name'),
                    selected: this.state ?? [],
                    productId: {{ $record?->getKey() ?? 'null' }},
                })
            },
            picked(event) {
                if (event.detail.target !== '{{ $statePath }}') return
                event.detail.items.forEach((item) => (this.thumbs[item.id] = item.url))
                this.state = event.detail.items.map((item) => item.id)
            },
            remove(id) { this.state = this.state.filter((i) => i !== id) },
            makeMain(id) { this.state = [id, ...this.state.filter((i) => i !== id)] },
            dragging: null,
            drop(target) {
                if (this.dragging === null || this.dragging === target) return
                const list = this.state.filter((i) => i !== this.dragging)
                list.splice(list.indexOf(target) + (this.state.indexOf(this.dragging) < this.state.indexOf(target) ? 1 : 0), 0, this.dragging)
                this.state = list
                this.dragging = null
            },
         }"
         x-on:images-picked.window="picked($event)"
         class="space-y-3">

        <div class="flex flex-wrap items-center gap-3">
            <x-filament::button type="button" icon="heroicon-m-photo" x-on:click="open()">آپلود / انتخاب تصاویر</x-filament::button>
            <span class="text-xs text-gray-500">پوشه‌ی این محصول خودکار باز می‌شود. اولین تصویر، تصویر اصلی است؛ برای جابه‌جایی بکشید.</span>
        </div>

        <template x-if="state && state.length">
            <ul class="grid grid-cols-3 gap-2">
                <template x-for="(id, index) in state" :key="id">
                    <li draggable="true" x-on:dragstart="dragging = id" x-on:dragend="dragging = null"
                        x-on:dragover.prevent x-on:drop.prevent="drop(id)"
                        :class="dragging === id && 'opacity-40'"
                        class="group relative aspect-square cursor-move overflow-hidden rounded-xl bg-gray-100 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                        <img :src="thumbs[id]" alt="" class="size-full object-cover" loading="lazy">
                        <span x-show="index === 0" class="absolute inset-x-1.5 bottom-1.5 rounded-md bg-primary-600 py-0.5 text-center text-[11px] font-bold text-white">تصویر اصلی</span>
                        <span x-show="index > 0" class="absolute start-1.5 top-1.5 grid size-6 place-items-center rounded-full bg-gray-950/60 text-xs font-bold text-white" x-text="index + 1"></span>
                        <div class="absolute end-1.5 top-1.5 flex gap-1 opacity-0 transition group-hover:opacity-100">
                            <button type="button" x-show="index > 0" x-on:click="makeMain(id)" class="rounded-md bg-white/90 p-1 text-gray-700 shadow hover:text-primary-600" title="تصویر اصلی شود">
                                <x-filament::icon icon="heroicon-m-star" class="size-4" />
                            </button>
                            <button type="button" x-on:click="remove(id)" class="rounded-md bg-white/90 p-1 text-gray-700 shadow hover:text-danger-600" title="حذف از محصول">
                                <x-filament::icon icon="heroicon-m-x-mark" class="size-4" />
                            </button>
                        </div>
                    </li>
                </template>
            </ul>
        </template>
        <p x-show="! state || ! state.length" class="rounded-xl border border-dashed border-gray-200 py-6 text-center text-sm text-gray-500 dark:border-white/10">هنوز تصویری انتخاب نشده.</p>
    </div>
</x-dynamic-component>
