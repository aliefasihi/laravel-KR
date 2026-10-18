{{-- Header: login / my account | نویسنده: علی فصیحی (Ali Fasihi) --}}
@if ($customer = auth('customer')->user())
    <a href="{{ route('customer.account') }}" class="btn-glass hidden !px-4 !py-2.5 lg:inline-flex" title="حساب کاربری">
        <span class="grid size-6 place-items-center rounded-full bg-gradient-to-br from-rose to-caramel text-[11px] font-black text-white">{{ mb_substr($customer->first_name ?: 'خ', 0, 1) }}</span>
        <span class="max-w-28 truncate">{{ $customer->first_name ?: 'حساب من' }}</span>
    </a>
@else
    <a href="{{ route('customer.login') }}" class="btn-navy hidden !px-5 !py-2.5 lg:inline-flex" data-no-prerender>
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0" stroke-linecap="round"/></svg>
        ورود | ثبت‌نام
    </a>
@endif
