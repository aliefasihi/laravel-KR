<?php

/**
 * Khaneh Romantic — Low-stock alerts
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * A published variant whose stock falls to the threshold (تنظیمات ← موجودی انبار, 3 by default)
 * is announced ONCE: a notification in the panel's bell for every admin and, when a template is
 * set, one SMS to the shop with the first product and how many more ran low with it. Restocking
 * above the threshold clears the mark, so the next run-down is announced again.
 *
 * scan() runs right after an order or a stock edit (after the response) and every 30 minutes.
 * SMS waits for the day (09:00–21:00 Tehran): at night the scan leaves everything for the morning.
 */

namespace Modules\Inventory\Support;

use App\Core\Modules\Modules;
use App\Core\Settings\Settings;
use App\Core\Support\PersianText;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Modules\Catalog\Models\ProductVariant;
use Modules\Inventory\Filament\Resources\LowStock\LowStockResource;
use Modules\Sms\Support\Sms;
use Throwable;

final class LowStock
{
    public static function threshold(): int
    {
        return max(0, (int) Settings::get('inventory.threshold', 3));
    }

    /** Published, sellable variants at or below the threshold (out of stock included). */
    public static function query(): Builder
    {
        return ProductVariant::query()
            ->where('product_variants.is_active', true)
            ->where('product_variants.stock', '<=', self::threshold())
            ->whereHas('product', fn ($q) => $q->active());
    }

    /** Run one scan after this response, however many orders/edits happened in it. */
    public static function later(): void
    {
        if (app()->bound('inventory.scan_queued')) {
            return;
        }
        app()->instance('inventory.scan_queued', true);
        dispatch(function () {
            app()->forgetInstance('inventory.scan_queued');
            rescue(fn () => app(self::class)->scan(), report: true);
        })->afterResponse();
    }

    /** @return int how many variants were newly announced */
    public function scan(): int
    {
        $threshold = self::threshold();

        // restocked → may be announced again next time
        ProductVariant::query()->whereNotNull('low_stock_notified_at')->where('stock', '>', $threshold)
            ->toBase()->update(['low_stock_notified_at' => null]);

        $sms = $this->smsTemplate();
        if ($sms !== null && ! $this->daytime()) {
            return 0;   // the morning scan sends it
        }

        $fresh = self::query()->whereNull('low_stock_notified_at')->with('product:id,name')->orderBy('stock')->limit(500)->get();
        if ($fresh->isEmpty()) {
            return 0;
        }
        ProductVariant::query()->whereKey($fresh->modelKeys())->toBase()->update(['low_stock_notified_at' => now()]);

        $this->bell($fresh);
        if ($sms !== null) {
            $this->sms($fresh, $sms);
        }

        return $fresh->count();
    }

    public static function label(ProductVariant $variant): string
    {
        $options = array_filter(array_map('strval', (array) $variant->options));

        return trim(($variant->product?->name ?? $variant->sku).($options ? ' — '.implode('، ', $options) : ''));
    }

    private function bell(Collection $variants): void
    {
        if (! Settings::get('inventory.bell', true)) {
            return;
        }
        $admins = User::query()->where('is_admin', true)->get();
        if ($admins->isEmpty()) {
            return;
        }
        $out = $variants->where('stock', '<=', 0)->count();
        $first = $variants->first();
        $more = $variants->count() - 1;

        Notification::make()
            ->title($out === $variants->count() ? 'موجودی تمام شد' : 'موجودی کم شد')
            ->body(self::label($first).' ('.PersianText::toPersianDigits(max(0, (int) $first->stock)).' عدد)'
                .($more > 0 ? ' و '.PersianText::toPersianDigits($more).' مدل دیگر' : ''))
            ->icon('heroicon-o-archive-box-x-mark')
            ->iconColor($out > 0 ? 'danger' : 'warning')
            ->actions([Action::make('open')->label('فهرست کم‌موجودها')->url(LowStockResource::getUrl())->markAsRead()])
            ->sendToDatabase($admins);
    }

    /** values: STOCK, MORE (other variants), LINK (admin list), PRODUCT — Kavenegar: token, token2, token3, token10 */
    private function sms(Collection $variants, string $template): void
    {
        $first = $variants->first();
        $values = [
            PersianText::toPersianDigits(max(0, (int) $first->stock)),
            PersianText::toPersianDigits($variants->count() - 1),
            LowStockResource::getUrl(),
            mb_substr(self::label($first), 0, 60),
        ];
        foreach ($this->mobiles() as $mobile) {
            try {
                app(Sms::class)->template($mobile, $template, $values);
            } catch (Throwable $e) {
                Log::warning('Low-stock SMS failed: '.$e->getMessage());
            }
        }
    }

    private function smsTemplate(): ?string
    {
        $template = Settings::get('inventory.sms_template');

        return $template && Modules::enabled('Sms') && $this->mobiles() !== [] ? (string) $template : null;
    }

    /** @return list<string> the shop's numbers (this tab, or the «new order» numbers) */
    private function mobiles(): array
    {
        $raw = Settings::get('inventory.mobiles') ?: Settings::get('order_sms.admin_mobiles');

        $mobiles = [];
        foreach (preg_split('/[\s,،]+/u', PersianText::toLatinDigits((string) $raw)) ?: [] as $mobile) {
            $mobile = preg_replace('/^(\+?98|0098)/', '0', $mobile);
            if (preg_match('/^09\d{9}$/', $mobile)) {
                $mobiles[] = $mobile;
            }
        }

        return array_values(array_unique($mobiles));
    }

    private function daytime(): bool
    {
        $hour = (int) now()->setTimezone(config('khaneh.display_timezone', 'Asia/Tehran'))->format('G');

        return $hour >= 9 && $hour < 21;
    }
}
