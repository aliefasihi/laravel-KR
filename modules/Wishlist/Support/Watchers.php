<?php

/**
 * Khaneh Romantic — «Back in stock» and «price dropped» SMS
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * check($productId) runs after any price/stock change of that product (after the response) and the
 * scheduler sweeps everything hourly (sales that start on a timer, messages held back at night):
 *  - in stock again → each pending «tell me» request gets one SMS, then it is done;
 *  - cheapest in-stock price at least 1% below what a wishlist item last saw → one SMS, and the item
 *    now watches the new price (so a later, further drop is announced again).
 * Quiet hours: nothing is sent 21:00–09:00 (Tehran); it waits for the next hourly sweep.
 * Templates (Kavenegar/sms.ir) in تنظیمات ← علاقه‌مندی‌ها; values: PRICE, OLD, LINK, PRODUCT.
 */

namespace Modules\Wishlist\Support;

use App\Core\Modules\Modules;
use App\Core\Settings\Settings;
use App\Core\Support\Money;
use Illuminate\Support\Facades\Log;
use Modules\Catalog\Models\Product;
use Modules\Sms\Support\Sms;
use Modules\Wishlist\Models\StockAlert;
use Modules\Wishlist\Models\WishlistItem;
use Throwable;

final class Watchers
{
    /** Cheapest current price (Rials) among variants in stock, or null when nothing is in stock. */
    public static function offerPrice(Product $product): ?int
    {
        return $product->variants->where('is_active', true)->where('stock', '>', 0)
            ->map(fn ($v) => $v->currentPrice())->min();
    }

    public function check(int $productId): void
    {
        $product = Product::query()->active()->with('variants')->find($productId);
        $price = $product ? self::offerPrice($product) : null;
        if ($product === null || $price === null) {
            return;
        }

        if ($this->canSend()) {
            $this->backInStock($product, $price);
        }
        $this->priceDrops($product, $price);
    }

    /** Everything waiting: products with open requests or saved by someone. */
    public function sweep(): void
    {
        StockAlert::query()->pending()->distinct()->pluck('product_id')
            ->merge(WishlistItem::query()->distinct()->pluck('product_id'))
            ->unique()
            ->each(fn (int $id) => $this->check($id));
    }

    public function canSend(): bool
    {
        if (! Settings::get('wishlist.quiet_hours', true)) {
            return true;
        }
        $hour = (int) now()->setTimezone(config('khaneh.display_timezone', 'Asia/Tehran'))->format('G');

        return $hour >= 9 && $hour < 21;
    }

    private function backInStock(Product $product, int $price): void
    {
        $template = Settings::get('wishlist.back_in_stock_template');
        if (! $template) {
            return;   // no template yet: keep the requests until there is one
        }
        StockAlert::query()->pending()->where('product_id', $product->id)->get()
            ->unique('mobile')
            ->each(function (StockAlert $alert) use ($product, $price, $template) {
                if ($this->send($alert->mobile, (string) $template, [Money::toman($price), '-', $product->url(), $product->name])) {
                    StockAlert::query()->pending()->where('product_id', $product->id)->where('mobile', $alert->mobile)->update(['notified_at' => now()]);
                }
            });
    }

    private function priceDrops(Product $product, int $price): void
    {
        $template = Settings::get('wishlist.price_drop_template');

        WishlistItem::query()->with('customer')->where('product_id', $product->id)->get()
            ->each(function (WishlistItem $item) use ($product, $price, $template) {
                // saved while out of stock (or just created): start watching from today's price
                if ($item->watched_price === null || $price > $item->watched_price) {
                    $item->update(['watched_price' => $price]);

                    return;
                }
                if ($price > $item->watched_price * 0.99 || ! $template || ! $this->canSend() || ! $item->customer?->mobile) {
                    return;
                }
                if ($this->send($item->customer->mobile, (string) $template, [Money::toman($price), Money::toman($item->watched_price), $product->url(), $product->name])) {
                    $item->update(['watched_price' => $price, 'price_notified_at' => now()]);
                }
            });
    }

    /** values: PRICE, OLD, LINK, PRODUCT (Kavenegar: token, token2, token3, token10) */
    private function send(string $mobile, string $template, array $values): bool
    {
        if (! Modules::enabled('Sms')) {
            return false;
        }
        try {
            app(Sms::class)->template($mobile, $template, $values);

            return true;
        } catch (Throwable $e) {
            Log::warning('Wishlist SMS failed: '.$e->getMessage());

            return false;
        }
    }
}
