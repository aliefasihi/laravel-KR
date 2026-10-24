<?php

/**
 * Khaneh Romantic — Admin «سرعت سایت»: what slows this install down, plus one-click optimize / clear
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace App\Filament\Pages;

use App\Core\Support\SpeedCheck;
use App\Core\Support\StoreCache;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use UnitEnum;

class SpeedPage extends Page
{
    protected string $view = 'filament.pages.speed';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static string|UnitEnum|null $navigationGroup = 'تنظیمات';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'سرعت سایت';

    protected static ?string $title = 'سرعت سایت';

    protected static ?string $slug = 'speed';

    /** @return list<array<string, string>> */
    public function getChecks(): array
    {
        return SpeedCheck::all();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('optimize')->label('بهینه‌سازی')->icon('heroicon-o-bolt')
                ->requiresConfirmation()
                ->modalDescription('تنظیمات، مسیرها، رویدادها، قالب‌ها، آیکن‌ها و اجزای پنل یک بار ساخته و نگه داشته می‌شوند (php artisan optimize). بعد از تغییر .env یا به‌روزرسانی کد دوباره بزنید.')
                ->action(function () {
                    $ok = rescue(fn () => Artisan::call('optimize') === 0, false);
                    Notification::make()->title($ok ? 'سایت بهینه شد' : 'بهینه‌سازی کامل نشد؛ لاگ را ببینید')->{$ok ? 'success' : 'danger'}()->send();
                    $this->redirect(static::getUrl());
                }),
            Action::make('clear')->label('پاک کردن کش‌ها')->icon('heroicon-o-arrow-path')->color('gray')
                ->requiresConfirmation()
                ->modalDescription('همه‌ی کش‌های ساخته‌شده و کش صفحه‌های فروشگاه پاک می‌شوند (برای حالت توسعه، یا وقتی تغییری دیده نمی‌شود). سرعت تا «بهینه‌سازی» بعدی کمتر است.')
                ->action(function () {
                    rescue(fn () => Artisan::call('optimize:clear'), report: false);
                    StoreCache::flush();
                    Notification::make()->title('کش‌ها پاک شدند')->success()->send();
                    $this->redirect(static::getUrl());
                }),
        ];
    }
}
