{{-- Profile form | نویسنده: علی فصیحی (Ali Fasihi) --}}
<form wire:submit="save" class="grid gap-4 sm:grid-cols-2" novalidate>
    @foreach (['first_name' => 'نام', 'last_name' => 'نام خانوادگی'] as $field => $label)
        <div>
            <label for="p-{{ $field }}" class="mb-1.5 block text-sm font-bold text-navy">{{ $label }}</label>
            <input id="p-{{ $field }}" type="text" wire:model="{{ $field }}" autocomplete="{{ $field === 'first_name' ? 'given-name' : 'family-name' }}" class="field">
            @error($field)<p class="mt-1.5 text-xs text-rose">{{ $message }}</p>@enderror
        </div>
    @endforeach
    <div>
        <label for="p-email" class="mb-1.5 block text-sm font-bold text-navy">ایمیل <span class="font-normal text-subtle">(اختیاری)</span></label>
        <input id="p-email" type="email" dir="ltr" wire:model="email" autocomplete="email" class="field">
        @error('email')<p class="mt-1.5 text-xs text-rose">{{ $message }}</p>@enderror
    </div>
    <div>
        <span class="mb-1.5 block text-sm font-bold text-navy">موبایل</span>
        <p class="field !bg-navy/5 text-muted" dir="ltr">{{ \App\Core\Support\PersianText::toPersianDigits(auth('customer')->user()->mobile) }}</p>
    </div>
    <label class="flex cursor-pointer items-center gap-3 sm:col-span-2">
        <input type="checkbox" wire:model="newsletter" class="size-[18px] rounded-md accent-rose">
        <span class="text-sm text-navy">خبر تخفیف‌ها و کالکشن‌های تازه را برایم بفرستید</span>
    </label>
    <div class="sm:col-span-2">
        <button type="submit" class="btn-navy" wire:loading.attr="disabled">ذخیره‌ی تغییرات</button>
    </div>
</form>
