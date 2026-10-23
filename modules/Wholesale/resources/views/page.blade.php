{{-- «همکاری و خرید عمده»: about + apply / status / the wholesale price list | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')
@php
    use App\Core\Support\{Money, PersianText};
    use Modules\Wholesale\Support\Wholesale;
    $best = collect($tiers)->max('percent');
    $approved = $account?->isApproved();
@endphp

@section('title', 'همکاری و خرید عمده')
@section('description', 'ثبت درخواست همکاری برای مغازه‌دارها، فروشگاه‌های اینترنتی و دکوراتورها و خرید عمده‌ی محصولات '.config('khaneh.store.name').'.')

@section('content')
    <div class="mx-auto max-w-7xl px-3 pt-5 sm:px-4 sm:pt-6">
        <x-breadcrumbs :items="['همکاری و خرید عمده' => null]" />

        @if (session('status'))
            <p class="glass mb-5 rounded-2xl px-4 py-3 text-sm font-bold text-ok" role="status">{{ session('status') }}</p>
        @endif

        @if ($approved)
            <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-black text-navy">لیست قیمت همکار</h1>
                    <p class="mt-1 text-sm text-muted">{{ $account->shop_name }} — قیمت‌های سبد خرید شما همین قیمت‌های همکار است.</p>
                </div>
                <div class="flex flex-wrap gap-2 text-xs font-bold">
                    @foreach ($tiers as $tier)
                        <span class="rounded-full bg-ok/10 px-3 py-1.5 text-ok">از {{ PersianText::toPersianDigits($tier['min_qty']) }} عدد: {{ Wholesale::percent($tier['percent']) }}٪</span>
                    @endforeach
                    @if ($minimum)
                        <span class="rounded-full bg-caramel-100 px-3 py-1.5 text-caramel-600">حداقل خرید {{ Money::toman($minimum) }} تومان</span>
                    @endif
                </div>
            </div>
            <livewire:wholesale.price-list />
        @else
            <section class="glass-strong relative overflow-hidden rounded-[var(--radius-glass)] p-6 sm:p-10">
                <div class="max-w-2xl">
                    <p class="text-sm font-bold text-rose">همکاری با {{ config('khaneh.store.name') }}</p>
                    <h1 class="mt-2 text-2xl font-black leading-[1.6] text-navy sm:text-3xl">خرید عمده برای مغازه‌ها، فروشگاه‌های اینترنتی و دکوراتورها</h1>
                    <p class="mt-3 leading-8 text-muted">{{ $intro ?: 'درخواست همکاری بدهید؛ بعد از تأیید، همه‌ی محصولات سایت را با قیمت همکار می‌بینید و می‌خرید — هرچه تعداد بیشتر، قیمت بهتر.' }}</p>
                </div>
                <ul class="mt-7 grid gap-3 sm:grid-cols-3">
                    @foreach ([
                        [$best ? 'تا '.Wholesale::percent($best).'٪ زیر قیمت سایت' : 'قیمت ویژه‌ی همکار', 'قیمت پله‌ای: با بیشتر شدن تعداد هر مدل، تخفیف بیشتر.'],
                        ['لیست قیمت و سفارش سریع', 'همه‌ی مدل‌ها در یک جدول؛ تعداد را بنویسید و یک‌جا به سبد اضافه کنید.'],
                        ['همان سبد و پرداخت همیشگی', 'سفارش‌ها مثل همیشه ثبت، پیگیری و ارسال می‌شوند.'],
                    ] as [$title, $text])
                        <li class="glass rounded-2xl p-4">
                            <p class="font-black text-navy">{{ $title }}</p>
                            <p class="mt-1 text-sm leading-7 text-muted">{{ $text }}</p>
                        </li>
                    @endforeach
                </ul>
            </section>

            <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_22rem] [&>*]:min-w-0">
                <section class="glass rounded-[var(--radius-glass)] p-5 sm:p-7" aria-labelledby="apply-title">
                    <h2 id="apply-title" class="text-xl font-black text-navy">درخواست همکاری</h2>
                    @if (! $customer)
                        <p class="mt-3 leading-8 text-muted">برای ثبت درخواست، اول با شماره‌ی موبایل وارد حساب کاربری شوید.</p>
                        <a href="{{ route('customer.login', ['back' => '/wholesale']) }}" class="btn-rose mt-5">ورود و ثبت درخواست</a>
                    @elseif ($account?->status === 'pending')
                        <div class="mt-4 rounded-2xl bg-caramel-100/70 p-4 leading-8 text-ink">
                            <p class="font-bold">درخواست «{{ $account->shop_name }}» در حال بررسی است.</p>
                            <p class="text-sm text-muted">نتیجه را پیامک می‌کنیم؛ بعد از تأیید، با همین حساب قیمت‌ها برایتان عمده می‌شود.</p>
                        </div>
                    @elseif ($account?->status === 'suspended')
                        <div class="mt-4 rounded-2xl bg-rose-100/60 p-4 leading-8 text-ink">
                            <p class="font-bold">همکاری شما فعلاً متوقف است.</p>
                            @if ($account->admin_note)<p class="text-sm">{{ $account->admin_note }}</p>@endif
                            <p class="text-sm text-muted">برای پیگیری با پشتیبانی تماس بگیرید.</p>
                        </div>
                    @else
                        @if ($account?->status === 'rejected')
                            <div class="mt-4 rounded-2xl bg-rose-100/60 p-4 text-sm leading-7 text-ink">
                                <p class="font-bold">درخواست قبلی تأیید نشد{{ $account->admin_note ? ':' : '.' }}</p>
                                @if ($account->admin_note)<p>{{ $account->admin_note }}</p>@endif
                                <p class="text-muted">می‌توانید اطلاعات را کامل کنید و دوباره بفرستید.</p>
                            </div>
                        @endif
                        <livewire:wholesale.apply />
                    @endif
                </section>

                <aside class="space-y-4">
                    @if ($tiers)
                        <div class="glass rounded-[var(--radius-glass)] p-5">
                            <h2 class="font-black text-navy">قیمت همکار</h2>
                            <ul class="mt-3 divide-y divide-line text-sm">
                                @foreach ($tiers as $tier)
                                    <li class="flex justify-between py-2">
                                        <span class="text-muted">از {{ PersianText::toPersianDigits($tier['min_qty']) }} عدد هر مدل</span>
                                        <span class="font-black text-ok">{{ Wholesale::percent($tier['percent']) }}٪ زیر قیمت</span>
                                    </li>
                                @endforeach
                            </ul>
                            <p class="mt-2 text-xs leading-6 text-subtle">برای بعضی دسته‌ها و برندها ممکن است متفاوت باشد.</p>
                        </div>
                    @endif
                    @if ($terms || $minimum)
                        <div class="glass rounded-[var(--radius-glass)] p-5">
                            <h2 class="font-black text-navy">شرایط همکاری</h2>
                            @if ($minimum)
                                <p class="mt-2 text-sm text-ink">حداقل خرید برای قیمت همکار: <b>{{ Money::toman($minimum) }} تومان</b></p>
                            @endif
                            @if ($terms)
                                <div class="mt-2 text-sm leading-7 text-muted">{!! nl2br(e($terms)) !!}</div>
                            @endif
                        </div>
                    @endif
                </aside>
            </div>
        @endif

        @if (! $approved && \App\Core\Modules\Modules::enabled('Crm'))
            <section class="glass mt-6 rounded-[var(--radius-glass)] p-5 sm:p-7" aria-labelledby="inquiry-title">
                <h2 id="inquiry-title" class="text-xl font-black text-navy">خرید سازمانی یا یک‌باره با تعداد بالا؟</h2>
                <p class="mt-1 text-sm leading-7 text-muted">برای هدیه‌ی سازمانی، مراسم یا یک سفارش بزرگ، بدون ثبت همکاری بنویسید چه می‌خواهید؛ قیمت و زمان تحویل را خبر می‌دهیم.</p>
                <livewire:wholesale.inquiry />
            </section>
        @endif
    </div>
@endsection
