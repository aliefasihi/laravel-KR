<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\AbandonedCart\Filament\Resources\AbandonedCarts\Pages;

use App\Core\Support\Money;
use App\Core\Support\PersianText;
use Filament\Resources\Pages\ListRecords;
use Modules\AbandonedCart\Filament\Resources\AbandonedCarts\AbandonedCartResource;
use Modules\AbandonedCart\Models\AbandonedCart;

class ListAbandonedCarts extends ListRecords
{
    protected static string $resource = AbandonedCartResource::class;

    /** «last 30 days: N reminders, M came back and bought X toman» */
    public function getSubheading(): ?string
    {
        $recent = AbandonedCart::query()->where('reminded_at', '>=', now()->subDays(30));
        $reminded = (clone $recent)->count();
        $back = (clone $recent)->whereNotNull('recovered_at');

        return '۳۰ روز اخیر: '.PersianText::toPersianDigits($reminded).' پیامک یادآوری · '
            .PersianText::toPersianDigits((clone $back)->count()).' خرید برگشته · '
            .Money::toman((int) (clone $back)->sum('order_total')).' تومان فروش برگشته';
    }
}
