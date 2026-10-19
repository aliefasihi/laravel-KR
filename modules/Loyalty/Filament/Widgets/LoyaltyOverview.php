<?php

/**
 * Khaneh Romantic — Club at a glance: wallet credit owed, points outstanding, members, referrals
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Loyalty\Filament\Widgets;

use App\Core\Support\Money;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Modules\Loyalty\Support\Loyalty;

class LoyaltyOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 5;

    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $fa = fn ($n) => Loyalty::fa((int) $n);
        $wallet = (int) DB::table('loyalty_wallets')->sum('balance');
        $points = (int) DB::table('loyalty_points_accounts')->where('balance', '>', 0)->sum('balance');
        $levels = DB::table('loyalty_customer_levels')->join('loyalty_levels', 'loyalty_levels.id', '=', 'loyalty_customer_levels.level_id')
            ->selectRaw('loyalty_levels.name as name, count(*) as c')->groupBy('loyalty_levels.name', 'loyalty_levels.min_spent')->orderBy('loyalty_levels.min_spent')->pluck('c', 'name');
        $rewarded = DB::table('loyalty_referrals')->where('status', 'rewarded')->where('rewarded_at', '>=', now()->subDays(30))->count();

        return [
            Stat::make('اعتبار کیف پول مشتریان', Money::toman($wallet).' تومان')->description('بدهی فروشگاه به مشتریان')->color('warning'),
            Stat::make('امتیازهای قابل تبدیل', $fa($points))->description('ارزش: '.Money::toman($points * Loyalty::pointValue()).' تومان'),
            Stat::make('اعضای هر سطح', $levels->isEmpty() ? '—' : $levels->map(fn ($c, $n) => $n.' '.$fa($c))->implode(' · ')),
            Stat::make('معرفی‌های موفق (۳۰ روز)', $fa($rewarded))->description('در کل: '.$fa(DB::table('loyalty_referrals')->count()).' معرفی'),
        ];
    }
}
