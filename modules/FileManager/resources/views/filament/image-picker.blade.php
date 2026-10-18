{{-- Product image picker modal | نویسنده: علی فصیحی (Ali Fasihi) --}}
<div x-data="{ open: @entangle('open') }" x-on:keydown.escape.window="open = false">
    <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-[60] flex items-center justify-center bg-gray-950/50 p-3 backdrop-blur-sm sm:p-6">
        <div x-show="open" x-transition @click.outside="open = false"
             class="flex max-h-full w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">

            {{-- header --}}
            <div class="flex items-center justify-between gap-4 border-b border-gray-100 px-5 py-4 dark:border-white/10">
                <div class="min-w-0">
                    <h2 class="text-base font-bold text-gray-950 dark:text-white">تصاویر محصول</h2>
                    @if ($this->folder)
                        <p class="mt-1 flex flex-wrap items-center gap-1 text-xs text-gray-500">
                            <x-filament::icon icon="heroicon-m-folder-open" class="size-4 text-primary-500" />
                            @foreach ($this->folder->trail() as $crumb)
                                <span @class(['font-bold text-gray-700 dark:text-gray-200' => $loop->last]) @if ($loop->last) dir="ltr" @endif>{{ $crumb->name }}</span>
                                @unless ($loop->last)<span class="text-gray-300">/</span>@endunless
                            @endforeach
                        </p>
                    @endif
                </div>
                <button type="button" x-on:click="open = false" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/5" aria-label="بستن">
                    <x-filament::icon icon="heroicon-m-x-mark" class="size-5" />
                </button>
            </div>

            @if ($problem)
                <div class="m-5 rounded-xl bg-warning-50 p-4 text-sm text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">{{ $problem }}</div>
            @else
                <div class="flex-1 space-y-4 overflow-y-auto p-5">
                    {{-- upload --}}
                    <label class="relative flex cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-gray-200 px-4 py-6 text-center transition hover:border-primary-400 hover:bg-primary-50/40 dark:border-white/10 dark:hover:bg-primary-500/5"
                           x-on:dragover.prevent="$el.classList.add('!border-primary-500')" x-on:dragleave="$el.classList.remove('!border-primary-500')"
                           x-on:drop="$el.classList.remove('!border-primary-500')">
                        <input type="file" wire:model="uploads" multiple accept="image/*" class="absolute inset-0 size-full cursor-pointer opacity-0" title="">
                        <x-filament::icon icon="heroicon-o-cloud-arrow-up" class="size-8 text-primary-500" />
                        <span class="text-sm font-bold text-gray-800 dark:text-gray-100">آپلود تصاویر — چند فایل با هم</span>
                        <span class="text-xs text-gray-500">به ترتیبی که آپلود می‌کنید انتخاب می‌شوند؛ نام فایل و متن جایگزین خودکار است.</span>
                        <span wire:loading wire:target="uploads" class="text-xs font-bold text-primary-600">در حال آپلود…</span>
                    </label>
                    @error('uploads.*') <p class="text-sm text-danger-600">{{ $message }}</p> @enderror

                    {{-- images --}}
                    @if ($this->files->isEmpty())
                        <p class="py-8 text-center text-sm text-gray-500">این پوشه هنوز خالی است.</p>
                    @else
                        <div class="grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-6">
                            @foreach ($this->files as $file)
                                @php($position = array_search($file->id, $selected, true))
                                <button type="button" wire:click="toggle({{ $file->id }})" wire:key="pick-{{ $file->id }}"
                                        @class(['group relative aspect-square overflow-hidden rounded-xl bg-gray-100 ring-2 transition dark:bg-white/5',
                                            'ring-primary-500' => $position !== false,
                                            'ring-transparent hover:ring-gray-300 dark:hover:ring-white/20' => $position === false])>
                                    <img src="{{ $file->urlFor(320) }}" alt="" loading="lazy" class="size-full object-cover">
                                    @if ($position !== false)
                                        <span class="absolute start-1.5 top-1.5 grid size-7 place-items-center rounded-full bg-primary-600 text-sm font-black text-white shadow">{{ $position + 1 }}</span>
                                        @if ($position === 0)
                                            <span class="absolute inset-x-1.5 bottom-1.5 rounded-md bg-gray-950/70 py-0.5 text-center text-[11px] font-bold text-white">تصویر اصلی</span>
                                        @endif
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            {{-- footer --}}
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 px-5 py-3 dark:border-white/10">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    <span class="font-black text-primary-600">{{ count($selected) }}</span> تصویر انتخاب شده
                    <span class="text-xs text-gray-400">— به ترتیب کلیک؛ اولی تصویر اصلی است</span>
                </p>
                <div class="flex gap-2">
                    @if ($selected)
                        <x-filament::button color="gray" size="sm" wire:click="clearSelection">پاک کردن انتخاب</x-filament::button>
                    @endif
                    <x-filament::button color="gray" size="sm" x-on:click="open = false">انصراف</x-filament::button>
                    <x-filament::button size="sm" wire:click="confirm" :disabled="(bool) $problem">ثبت ترتیب و افزودن</x-filament::button>
                </div>
            </div>
        </div>
    </div>
</div>
