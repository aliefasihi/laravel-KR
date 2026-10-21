{{-- Account: my list — details form, share link, items with quantities and claims | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    use App\Core\Support\Money;
    use App\Core\Support\PersianText;
    use Modules\Registry\Support\RegistryPurchases;
@endphp
<div class="space-y-6">
    @if ($editing || ! $registry)
        <form wire:submit="save" class="glass space-y-4 rounded-[var(--radius-glass)] p-5 sm:p-7">
            <h1 class="text-xl font-black text-navy">{{ $registry ? 'ویرایش لیست' : 'ساخت لیست جهیزیه' }}</h1>
            @unless ($registry)
                <p class="text-sm leading-7 text-muted">محصولات دلخواهتان را از صفحه‌ی هر محصول به لیست اضافه کنید و لینکش را برای خانواده و دوستان بفرستید؛ هر قلمی که کسی خرید یا رزرو کرد، تیک می‌خورد تا تکراری خریده نشود.</p>
            @endunless
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block">
                    <span class="mb-1.5 block text-sm font-bold text-navy">عنوان لیست</span>
                    <input wire:model="title" type="text" class="field" required>
                    @error('title') <span class="mt-1 block text-xs text-rose">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm font-bold text-navy">نام‌ها <span class="font-normal text-muted">(اختیاری، مثلاً «سارا و علی»)</span></span>
                    <input wire:model="names" type="text" class="field">
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm font-bold text-navy">تاریخ مراسم <span class="font-normal text-muted">(اختیاری)</span></span>
                    <input wire:model="event_date" type="date" class="field" dir="ltr">
                </label>
                <div class="space-y-2 pt-7">
                    <label class="flex items-center gap-2 text-sm text-navy"><input wire:model="is_public" type="checkbox" class="size-[18px] accent-rose"> هرکس لینک را دارد، لیست را ببیند</label>
                    <label class="flex items-center gap-2 text-sm text-navy"><input wire:model="show_buyers" type="checkbox" class="size-[18px] accent-rose"> نام خریدارها را به من نشان بده</label>
                </div>
            </div>
            <label class="block">
                <span class="mb-1.5 block text-sm font-bold text-navy">پیام برای مهمان‌ها <span class="font-normal text-muted">(اختیاری)</span></span>
                <textarea wire:model="message" rows="3" class="field"></textarea>
            </label>
            <div class="flex gap-2">
                <button type="submit" class="btn-navy">ذخیره</button>
                @if ($registry)
                    <button type="button" wire:click="$set('editing', false)" class="btn-glass">انصراف</button>
                @endif
            </div>
        </form>
    @else
        <section class="glass rounded-[var(--radius-glass)] p-5 sm:p-7" x-data="share">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-xl font-black text-navy">{{ $registry->title }}</h1>
                    <p class="mt-1 text-sm text-muted">
                        {{ $registry->names }}@if ($registry->event_date) · {{ \App\Core\Support\Jalali::format($registry->event_date) }}@endif
                        · {{ PersianText::toPersianDigits($registry->items->count()) }} قلم
                    </p>
                </div>
                <button type="button" wire:click="$set('editing', true)" class="text-sm font-bold text-rose">ویرایش</button>
            </div>
            @if ($saved)
                <p class="mt-3 text-sm font-bold text-ok" role="status">ذخیره شد.</p>
            @endif
            <div class="mt-5 rounded-2xl bg-cream/70 p-4">
                <p class="text-sm font-bold text-navy">لینک لیست برای فرستادن به دیگران</p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <code class="min-w-0 flex-1 truncate rounded-xl bg-surface px-3 py-2 text-xs text-navy" dir="ltr">{{ $registry->url() }}</code>
                    <button type="button" @click="copy('{{ $registry->url() }}')" class="btn-navy !px-4 !py-2 text-xs"><span x-show="!copied">کپی لینک</span><span x-show="copied" x-cloak>کپی شد</span></button>
                    <a href="https://wa.me/?text={{ urlencode($registry->title.' '.$registry->url()) }}" target="_blank" rel="noopener" class="btn-glass !px-4 !py-2 text-xs">واتس‌اپ</a>
                    <a href="{{ $registry->url() }}" target="_blank" class="btn-glass !px-4 !py-2 text-xs">دیدن لیست</a>
                </div>
                @unless ($registry->is_public)
                    <p class="mt-2 text-xs text-rose">لیست فعلاً خصوصی است؛ فقط خودتان آن را می‌بینید.</p>
                @endunless
            </div>
        </section>

        <section class="glass rounded-[var(--radius-glass)] p-5 sm:p-7" aria-labelledby="items-title">
            <h2 id="items-title" class="text-lg font-black text-navy">اقلام لیست</h2>
            @if ($registry->items->isEmpty())
                <p class="mt-4 text-sm leading-7 text-muted">هنوز چیزی اضافه نکرده‌اید. در صفحه‌ی هر محصول، زیر دکمه‌ی خرید، «افزودن به {{ $registry->title }}» را بزنید.</p>
                <a href="{{ route('catalog.shop') }}" class="btn-navy mt-4">رفتن به فروشگاه</a>
            @else
                <ul class="mt-4 divide-y divide-line">
                    @foreach ($registry->items as $item)
                        @php($variant = RegistryPurchases::variant($item))
                        <li class="flex flex-wrap items-center gap-4 py-4" wire:key="item-{{ $item->id }}">
                            <a href="{{ $item->product->url() }}" class="size-16 shrink-0 overflow-hidden rounded-2xl bg-cream">
                                <x-media.picture :media="$item->product->media->first()" :alt="$item->product->name" sizes="64px" class="size-full object-cover" />
                            </a>
                            <div class="min-w-0 flex-1">
                                <a href="{{ $item->product->url() }}" class="line-clamp-1 text-sm font-bold text-navy hover:text-rose">{{ $item->product->name }}</a>
                                <p class="mt-1 text-xs text-muted">
                                    {{ $variant ? Money::toman($variant->currentPrice()).' تومان' : 'فعلاً ناموجود' }}
                                    · {{ PersianText::toPersianDigits($item->claimed()) }} از {{ PersianText::toPersianDigits($item->quantity) }} تهیه شده
                                </p>
                                @if ($registry->show_buyers && $item->claims->isNotEmpty())
                                    <p class="mt-1 text-xs text-ok">{{ $item->claims->map(fn ($c) => $c->name.' ('.\Modules\Registry\Models\RegistryClaim::KINDS[$c->kind].')')->implode('، ') }}</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-1 rounded-full border border-line p-1">
                                <button type="button" wire:click="setQuantity({{ $item->id }}, {{ $item->quantity + 1 }})" class="grid size-8 place-items-center rounded-full text-navy hover:bg-cream" aria-label="بیشتر">+</button>
                                <span class="w-6 text-center text-sm font-black text-navy">{{ PersianText::toPersianDigits($item->quantity) }}</span>
                                <button type="button" wire:click="setQuantity({{ $item->id }}, {{ $item->quantity - 1 }})" class="grid size-8 place-items-center rounded-full text-navy hover:bg-cream" aria-label="کمتر">−</button>
                            </div>
                            <button type="button" wire:click="remove({{ $item->id }})" wire:confirm="از لیست حذف شود؟" class="text-xs font-bold text-muted hover:text-rose">حذف</button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endif
</div>
