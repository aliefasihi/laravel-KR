<?php

/**
 * Khaneh Romantic — «کم‌موجودها»: to reorder / sold out / all published variants
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Inventory\Filament\Resources\LowStock\Pages;

use App\Core\Support\PersianText;
use App\Filament\Pages\SettingsPage;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Modules\Inventory\Filament\Resources\LowStock\LowStockResource;
use Modules\Inventory\Support\LowStock;

class ListLowStock extends ListRecords
{
    protected static string $resource = LowStockResource::class;

    public function getSubheading(): ?string
    {
        return 'حد هشدار: '.PersianText::toPersianDigits(LowStock::threshold()).' عدد و کمتر (تنظیمات ← موجودی انبار). «کافی برای» با سرعت فروش ۳۰ روز اخیر حساب شده؛ موجودی را همین‌جا می‌توانید عوض کنید.';
    }

    public function getTabs(): array
    {
        $threshold = LowStock::threshold();
        $base = fn () => LowStockResource::getEloquentQuery();
        $fa = fn (int $n) => $n ? PersianText::toPersianDigits($n) : null;

        return [
            'low' => Tab::make('نیاز به شارژ')->badge($fa($base()->where('stock', '<=', $threshold)->count()))->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('stock', '<=', $threshold)),
            'out' => Tab::make('تمام شده')->badge($fa($base()->where('stock', '<=', 0)->count()))->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('stock', '<=', 0)),
            'all' => Tab::make('همه‌ی مدل‌ها'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('settings')->label('تنظیم حد هشدار')->icon('heroicon-o-cog-6-tooth')->color('gray')
                ->url(fn () => SettingsPage::getUrl(['tab' => 'inventory'])),
        ];
    }
}
