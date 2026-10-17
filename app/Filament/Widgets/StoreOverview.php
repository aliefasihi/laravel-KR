<?php

/**
 * Khaneh Romantic — Dashboard: what needs attention today
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace App\Filament\Widgets;

use App\Core\Support\PersianText;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Content\Models\Post;
use Modules\Seo\Models\Redirect;

class StoreOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $fa = fn (int $n) => PersianText::toPersianDigits(number_format($n));

        $active = Product::query()->active()->count();
        $drafts = Product::query()->where('status', Product::STATUS_DRAFT)->count();
        $outOfStock = ProductVariant::query()->where('is_active', true)->where('stock', '<', 1)
            ->whereHas('product', fn ($q) => $q->active())->count();
        $noImage = Product::query()->active()->doesntHave('media')->count();
        $redirectHits = (int) Redirect::query()->where('last_hit_at', '>=', now()->subDays(30))->sum('hits');

        return [
            Stat::make('محصولات منتشرشده', $fa($active))
                ->description($fa($drafts).' پیش‌نویس'),
            Stat::make('مدل‌های ناموجود', $fa($outOfStock))
                ->description('محصول منتشرشده با موجودی صفر')
                ->color($outOfStock > 0 ? 'danger' : 'success'),
            Stat::make('محصولات بدون تصویر', $fa($noImage))
                ->color($noImage > 0 ? 'warning' : 'success'),
            Stat::make('مقاله‌های منتشرشده', $fa(Post::query()->published()->count())),
            Stat::make('ورود از آدرس‌های قدیمی', $fa($redirectHits))
                ->description('۳۰ روز اخیر، از طریق ریدایرکت'),
        ];
    }
}
