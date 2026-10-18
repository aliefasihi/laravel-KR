{{-- Mobile drawer: account | نویسنده: علی فصیحی (Ali Fasihi) --}}
<div class="px-4 pb-3">
    @if ($customer = auth('customer')->user())
        <a href="{{ route('customer.account') }}" class="btn-glass w-full">حساب کاربری — {{ $customer->first_name ?: 'من' }}</a>
    @else
        <a href="{{ route('customer.login') }}" class="btn-navy w-full" data-no-prerender>ورود | ثبت‌نام</a>
    @endif
</div>
