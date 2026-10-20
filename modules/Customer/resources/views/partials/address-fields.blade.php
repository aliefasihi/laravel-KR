{{--
    Delivery-address fields (inside a 2-column grid form) — account «نشانی‌ها» and checkout.
    $model: the Livewire array property («form», «address»); $id: prefix for element ids.
    نویسنده: علی فصیحی (Ali Fasihi)
--}}
@php
    $provinces = config('modules.customer.provinces');
    $fields = array_filter([
        'title' => ($withTitle ?? false) ? ['عنوان (مثلاً خانه)', ''] : null,
        'recipient_name' => ['نام گیرنده', 'autocomplete=name'],
        'recipient_mobile' => ['موبایل گیرنده', 'dir=ltr inputmode=numeric autocomplete=tel'],
    ]);
    $error = fn (string $name) => $errors->first($model.'.'.$name);
@endphp
@foreach ($fields as $name => [$label, $attrs])
    <div>
        <label for="{{ $id }}-{{ $name }}" class="mb-1.5 block text-sm font-bold text-navy">{{ $label }}</label>
        <input id="{{ $id }}-{{ $name }}" type="text" wire:model="{{ $model }}.{{ $name }}" {!! $attrs !!} class="field">
        @if ($message = $error($name))<p class="mt-1.5 text-xs text-rose">{{ $message }}</p>@endif
    </div>
@endforeach
<div>
    <label for="{{ $id }}-province" class="mb-1.5 block text-sm font-bold text-navy">استان</label>
    <select id="{{ $id }}-province" wire:model="{{ $model }}.province" class="field">
        @foreach ($provinces as $province)
            <option value="{{ $province }}">{{ $province }}</option>
        @endforeach
    </select>
    @if ($message = $error('province'))<p class="mt-1.5 text-xs text-rose">{{ $message }}</p>@endif
</div>
<div>
    <label for="{{ $id }}-city" class="mb-1.5 block text-sm font-bold text-navy">شهر</label>
    <input id="{{ $id }}-city" type="text" wire:model="{{ $model }}.city" autocomplete="address-level2" class="field">
    @if ($message = $error('city'))<p class="mt-1.5 text-xs text-rose">{{ $message }}</p>@endif
</div>
<div class="sm:col-span-2">
    <label for="{{ $id }}-address" class="mb-1.5 block text-sm font-bold text-navy">نشانی کامل</label>
    <textarea id="{{ $id }}-address" rows="2" wire:model="{{ $model }}.address" autocomplete="street-address" class="field" placeholder="خیابان، کوچه، …"></textarea>
    @if ($message = $error('address'))<p class="mt-1.5 text-xs text-rose">{{ $message }}</p>@endif
</div>
<div class="grid grid-cols-3 gap-3 sm:col-span-2">
    @foreach (['plaque' => 'پلاک', 'unit' => 'واحد', 'postal_code' => 'کد پستی'] as $name => $label)
        <div>
            <label for="{{ $id }}-{{ $name }}" class="mb-1.5 block text-sm font-bold text-navy">{{ $label }}</label>
            <input id="{{ $id }}-{{ $name }}" type="text" wire:model="{{ $model }}.{{ $name }}" @if ($name === 'postal_code') dir="ltr" inputmode="numeric" autocomplete="postal-code" @endif class="field">
            @if ($message = $error($name))<p class="mt-1.5 text-xs text-rose">{{ $message }}</p>@endif
        </div>
    @endforeach
</div>
