{{-- Wholesale application form | نویسنده: علی فصیحی (Ali Fasihi) --}}
<form wire:submit="submit" class="mt-5 grid gap-4 sm:grid-cols-2" novalidate>
    <div>
        <label for="ws-shop" class="mb-1.5 block font-bold text-navy">نام فروشگاه یا شرکت</label>
        <input id="ws-shop" type="text" wire:model="shop_name" class="field" maxlength="80" placeholder="مثلاً: گالری خانه‌ی نو">
        @error('shop_name')<p class="mt-1 text-xs text-rose">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="ws-owner" class="mb-1.5 block font-bold text-navy">نام و نام خانوادگی</label>
        <input id="ws-owner" type="text" wire:model="owner_name" class="field" maxlength="60">
        @error('owner_name')<p class="mt-1 text-xs text-rose">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label for="ws-type" class="mb-1.5 block font-bold text-navy">نوع کسب‌وکار</label>
        <select id="ws-type" wire:model="business_type" class="field">
            <option value="">انتخاب کنید…</option>
            @foreach ($types as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
        @error('business_type')<p class="mt-1 text-xs text-rose">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="ws-province" class="mb-1.5 block font-bold text-navy">استان</label>
        <select id="ws-province" wire:model="province" class="field">
            <option value="">انتخاب کنید…</option>
            @foreach ($provinces as $p)
                <option value="{{ $p }}">{{ $p }}</option>
            @endforeach
        </select>
        @error('province')<p class="mt-1 text-xs text-rose">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="ws-city" class="mb-1.5 block font-bold text-navy">شهر</label>
        <input id="ws-city" type="text" wire:model="city" class="field" maxlength="40">
        @error('city')<p class="mt-1 text-xs text-rose">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="ws-phone" class="mb-1.5 block font-bold text-navy">تلفن ثابت <span class="text-sm font-normal text-subtle">(اختیاری)</span></label>
        <input id="ws-phone" type="tel" dir="ltr" inputmode="numeric" wire:model="phone" class="field text-start" placeholder="021…">
        @error('phone')<p class="mt-1 text-xs text-rose">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="ws-web" class="mb-1.5 block font-bold text-navy">پیج اینستاگرام یا سایت <span class="text-sm font-normal text-subtle">(اختیاری)</span></label>
        <input id="ws-web" type="text" dir="ltr" wire:model="website" class="field text-start" placeholder="@shop  یا  shop.ir">
        @error('website')<p class="mt-1 text-xs text-rose">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label for="ws-desc" class="mb-1.5 block font-bold text-navy">توضیح <span class="text-sm font-normal text-subtle">(اختیاری)</span></label>
        <textarea id="ws-desc" rows="3" wire:model="description" maxlength="1000" class="field" placeholder="چه محصولاتی و حدوداً چه تعدادی در ماه لازم دارید؟"></textarea>
        @error('description')<p class="mt-1 text-xs text-rose">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label for="ws-doc" class="mb-1.5 block font-bold text-navy">جواز کسب یا عکس مغازه <span class="text-sm font-normal text-subtle">(اختیاری؛ بررسی را سریع‌تر می‌کند)</span></label>
        <input id="ws-doc" type="file" wire:model="document" accept="image/*,application/pdf" class="field file:me-3 file:rounded-full file:border-0 file:bg-navy/5 file:px-4 file:py-1.5 file:font-bold file:text-navy">
        <div wire:loading wire:target="document" class="mt-1 text-xs text-muted">در حال بارگذاری…</div>
        @error('document')<p class="mt-1 text-xs text-rose">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <button type="submit" class="btn-rose" wire:loading.attr="disabled" wire:target="submit">ثبت درخواست همکاری</button>
    </div>
</form>
