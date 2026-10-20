{{-- Addresses list + form | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php use App\Core\Support\PersianText; @endphp
<div>
    <div class="mb-5 flex items-center justify-between gap-3">
        <h1 class="text-xl font-black text-navy">نشانی‌ها</h1>
        @if ($editing === null)
            <button type="button" wire:click="create" class="btn-rose !px-5 !py-2.5">+ نشانی تازه</button>
        @endif
    </div>

    @if ($editing !== null)
        <form wire:submit="save" class="mb-6 grid gap-4 rounded-[var(--radius-card)] bg-surface/60 p-4 ring-1 ring-line sm:grid-cols-2 sm:p-6" novalidate>
            <p class="font-black text-navy sm:col-span-2">{{ $editing ? 'ویرایش نشانی' : 'نشانی تازه' }}</p>
            @include('customer::partials.address-fields', ['model' => 'form', 'id' => 'a', 'withTitle' => true])
            <label class="flex cursor-pointer items-center gap-3 sm:col-span-2">
                <input type="checkbox" wire:model="form.is_default" class="size-[18px] rounded-md accent-rose">
                <span class="text-sm text-navy">نشانی پیش‌فرض باشد</span>
            </label>
            <div class="flex gap-2 sm:col-span-2">
                <button type="submit" class="btn-navy" wire:loading.attr="disabled">ذخیره</button>
                <button type="button" wire:click="cancel" class="btn-glass">انصراف</button>
            </div>
        </form>
    @endif

    <div class="grid gap-3 md:grid-cols-2">
        @forelse ($addresses as $address)
            <article wire:key="addr-{{ $address->id }}" @class(['rounded-[var(--radius-card)] bg-surface/60 p-4 ring-1', 'ring-rose/40' => $address->is_default, 'ring-line' => ! $address->is_default])>
                <div class="mb-2 flex items-center gap-2">
                    <p class="font-black text-navy">{{ $address->title ?: 'نشانی' }}</p>
                    @if ($address->is_default)
                        <span class="rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-bold text-rose">پیش‌فرض</span>
                    @endif
                </div>
                <p class="text-sm leading-7 text-muted">{{ $address->line() }}</p>
                <p class="mt-1 text-xs text-subtle">
                    {{ $address->recipient_name }} — <span dir="ltr">{{ PersianText::toPersianDigits($address->recipient_mobile) }}</span>
                    @if ($address->postal_code) — کد پستی {{ PersianText::toPersianDigits($address->postal_code) }} @endif
                </p>
                <div class="mt-3 flex flex-wrap gap-3 text-xs font-bold">
                    <button type="button" wire:click="edit({{ $address->id }})" class="text-navy hover:text-rose">ویرایش</button>
                    @unless ($address->is_default)
                        <button type="button" wire:click="makeDefault({{ $address->id }})" class="text-navy hover:text-rose">پیش‌فرض کن</button>
                    @endunless
                    <button type="button" wire:click="delete({{ $address->id }})" wire:confirm="این نشانی حذف شود؟" class="text-muted hover:text-rose">حذف</button>
                </div>
            </article>
        @empty
            @if ($editing === null)
                <p class="text-sm text-muted md:col-span-2">هنوز نشانی ثبت نکرده‌اید.</p>
            @endif
        @endforelse
    </div>
</div>
