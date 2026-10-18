{{-- Admin: file manager | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php use Illuminate\Support\Number; @endphp
<x-filament-panels::page>
    <div class="space-y-5">
        {{-- breadcrumbs + search --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <nav class="flex flex-wrap items-center gap-1 text-sm" aria-label="مسیر پوشه">
                <button type="button" wire:click="open(null)" class="flex items-center gap-1 rounded-lg px-2 py-1 font-medium text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5">
                    <x-filament::icon icon="heroicon-m-home" class="size-4" /> همه‌ی پوشه‌ها
                </button>
                @foreach ($this->folder?->trail() ?? [] as $crumb)
                    <span class="text-gray-300">/</span>
                    <button type="button" wire:click="open({{ $crumb->id }})" @class(['rounded-lg px-2 py-1 hover:bg-gray-100 dark:hover:bg-white/5', 'font-bold text-gray-950 dark:text-white' => $loop->last, 'text-gray-600 dark:text-gray-300' => ! $loop->last])
                            @if ($crumb->kind === 'product') dir="ltr" @endif>{{ $crumb->name }}</button>
                @endforeach
            </nav>
            <div class="w-full sm:w-72">
                <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
                    <x-filament::input type="search" wire:model.live.debounce.400ms="search" placeholder="جستجوی تصویر در همه‌ی پوشه‌ها" />
                </x-filament::input.wrapper>
            </div>
        </div>

        {{-- folders --}}
        @if ($search === '' && $this->folders->isNotEmpty())
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
                @foreach ($this->folders as $folder)
                    <div wire:key="folder-{{ $folder->id }}" class="group relative flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-3 transition hover:border-primary-300 hover:shadow-sm dark:border-white/10 dark:bg-gray-900">
                        <button type="button" wire:click="open({{ $folder->id }})" class="flex min-w-0 flex-1 items-center gap-3 text-start">
                            <x-filament::icon :icon="$folder->kind === 'product' ? 'heroicon-s-folder' : 'heroicon-s-folder'" @class(['size-9 shrink-0', 'text-primary-500' => $folder->kind !== 'custom', 'text-warning-400' => $folder->kind === 'custom']) />
                            <span class="min-w-0">
                                <span @class(['block text-sm font-bold text-gray-900 dark:text-white', 'truncate' => $folder->kind !== 'product', 'break-all text-xs' => $folder->kind === 'product']) @if ($folder->kind === 'product') dir="ltr" @endif>{{ $folder->name }}</span>
                                <span class="text-xs text-gray-500">
                                    @if ($folder->children_count) {{ $folder->children_count }} پوشه @endif
                                    @if ($folder->children_count && $folder->files_count) · @endif
                                    @if ($folder->files_count) {{ $folder->files_count }} تصویر @endif
                                    @if (! $folder->children_count && ! $folder->files_count) خالی @endif
                                </span>
                            </span>
                        </button>
                        @unless ($folder->isSystem())
                            <div class="flex opacity-0 transition group-hover:opacity-100">
                                {{ ($this->renameFolderAction)(['id' => $folder->id]) }}
                                {{ ($this->deleteFolderAction)(['id' => $folder->id]) }}
                            </div>
                        @endunless
                    </div>
                @endforeach
            </div>
        @endif

        {{-- files --}}
        @php($files = $this->files->take($limit))
        @if ($files->isNotEmpty())
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                @foreach ($files as $file)
                    <div wire:key="file-{{ $file->id }}" class="group overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900"
                         x-data="{ copied: false }">
                        <a href="{{ $file->url() }}" target="_blank" class="block aspect-square bg-gray-100 dark:bg-white/5">
                            <img src="{{ $file->urlFor(320) }}" alt="{{ $file->alt }}" loading="lazy" class="size-full object-cover">
                        </a>
                        <div class="space-y-1 p-2">
                            <p class="truncate text-xs font-medium text-gray-800 dark:text-gray-200" dir="ltr" title="{{ $file->path }}">{{ basename($file->path) }}</p>
                            <p class="flex items-center justify-between text-[11px] text-gray-500">
                                <span>{{ $file->width ? $file->width.'×'.$file->height : '' }} {{ $file->size ? '· '.Number::fileSize($file->size) : '' }}</span>
                                @if ($n = $this->usage[$file->id] ?? 0)
                                    <x-filament::badge size="xs" color="success">{{ $n }} استفاده</x-filament::badge>
                                @endif
                            </p>
                            <div class="flex items-center justify-between">
                                <button type="button" class="text-[11px] font-bold text-primary-600"
                                        x-on:click="navigator.clipboard.writeText(@js($file->url())); copied = true; setTimeout(() => copied = false, 1500)">
                                    <span x-show="! copied">کپی آدرس</span><span x-show="copied" x-cloak>کپی شد ✓</span>
                                </button>
                                {{ ($this->deleteFileAction)(['id' => $file->id]) }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            @if ($this->files->count() > $limit)
                <div class="text-center"><x-filament::button color="gray" wire:click="loadMore">نمایش بیشتر</x-filament::button></div>
            @endif
        @elseif ($search !== '')
            <p class="py-10 text-center text-sm text-gray-500">تصویری با «{{ $search }}» پیدا نشد.</p>
        @elseif ($this->folder && $this->folders->isEmpty())
            <div class="rounded-2xl border-2 border-dashed border-gray-200 py-12 text-center text-sm text-gray-500 dark:border-white/10">
                این پوشه خالی است — از دکمه‌ی «آپلود» بالای صفحه تصویر اضافه کنید.
            </div>
        @endif
    </div>
</x-filament-panels::page>
