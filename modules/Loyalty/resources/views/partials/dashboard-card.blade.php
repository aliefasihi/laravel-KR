{{-- Hook «account.dashboard»: club summary | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    use App\Core\Support\{Money, PersianText};
    use Modules\Loyalty\Support\{Levels, Loyalty, Points, Wallet};
    $id = (int) auth('customer')->id();
    $level = Levels::current($id);
@endphp
<a href="{{ route('customer.club') }}" class="glass group flex flex-wrap items-center gap-4 rounded-[var(--radius-glass)] p-5 transition hover:bg-surface/85 sm:p-6">
    <span class="grid size-12 shrink-0 place-items-center rounded-full text-lg font-black text-white" style="background: {{ $level?->color ?? '#c9a36a' }}">★</span>
    <span class="min-w-0 flex-1">
        <span class="block font-black text-navy">باشگاه مشتریان @if ($level)<span class="text-sm font-bold text-muted">— سطح {{ $level->name }}</span>@endif</span>
        <span class="mt-1 block text-sm text-muted">
            @if (Loyalty::on('wallet')) کیف پول: <b class="text-navy">{{ Money::toman(Wallet::balance($id)) }}</b> تومان @endif
            @if (Loyalty::on('points')) · امتیاز: <b class="text-navy">{{ Loyalty::fa((int) Points::balance($id)) }}</b> @endif
        </span>
    </span>
    <span class="text-sm font-bold text-rose group-hover:underline">مشاهده ←</span>
</a>
