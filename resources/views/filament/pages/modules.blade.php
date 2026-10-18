{{-- Admin: modules (WordPress-style plugins) | نویسنده: علی فصیحی (Ali Fasihi) --}}
<x-filament-panels::page>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            هر قابلیت سایت یک ماژول است. ماژول‌های «هسته» همیشه روشن‌اند؛ بقیه را می‌توانید خاموش و روشن کنید.
            با خاموش کردن، داده‌ها پاک نمی‌شوند.
        </p>
        <div class="flex gap-1 rounded-xl bg-gray-100 p-1 text-sm dark:bg-white/5">
            @foreach (['all' => 'همه', 'active' => 'فعال', 'inactive' => 'غیرفعال', 'optional' => 'قابل‌تغییر'] as $key => $label)
                <button type="button" wire:click="$set('filter', '{{ $key }}')"
                        @class(['rounded-lg px-3 py-1.5 font-medium transition',
                            'bg-white text-primary-600 shadow-sm dark:bg-gray-800' => $filter === $key,
                            'text-gray-500 hover:text-gray-800 dark:hover:text-gray-200' => $filter !== $key])>{{ $label }}</button>
            @endforeach
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($this->getModules() as $module)
            <div wire:key="module-{{ $module['name'] }}" @class([
                'flex flex-col rounded-2xl border bg-white p-5 shadow-sm transition dark:bg-gray-900',
                'border-primary-200 dark:border-primary-500/30' => $module['enabled'] && ! $module['core'],
                'border-gray-200 dark:border-white/10' => ! $module['enabled'] || $module['core'],
            ])>
                <div class="flex items-start gap-3">
                    <span @class(['grid size-11 shrink-0 place-items-center rounded-xl',
                        'bg-primary-50 text-primary-600 dark:bg-primary-500/10' => $module['enabled'],
                        'bg-gray-100 text-gray-400 dark:bg-white/5' => ! $module['enabled']])>
                        <x-filament::icon :icon="'heroicon-o-'.$module['icon']" class="size-6" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-bold text-gray-900 dark:text-white">{{ $module['title'] }}</h3>
                            @if ($module['core'])
                                <x-filament::badge color="gray" size="sm">هسته</x-filament::badge>
                            @elseif ($module['enabled'])
                                <x-filament::badge color="success" size="sm">فعال</x-filament::badge>
                            @else
                                <x-filament::badge color="warning" size="sm">غیرفعال</x-filament::badge>
                            @endif
                        </div>
                        <p class="mt-0.5 font-mono text-[11px] text-gray-400" dir="ltr">{{ $module['name'] }} · v{{ $module['version'] }}</p>
                    </div>
                </div>

                <p class="mt-3 flex-1 text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $module['description'] }}</p>

                @if ($module['requiresTitles'])
                    <p class="mt-3 text-xs text-gray-500">نیازمند: {{ implode('، ', $module['requiresTitles']) }}</p>
                @endif

                @unless ($module['core'])
                    <div class="mt-4 flex items-center justify-end border-t border-gray-100 pt-3 dark:border-white/5">
                        {{ ($this->toggleAction)(['module' => $module['name']]) }}
                    </div>
                @endunless
            </div>
        @endforeach
    </div>

</x-filament-panels::page>
