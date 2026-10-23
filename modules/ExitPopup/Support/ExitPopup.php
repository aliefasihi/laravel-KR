<?php

/**
 * Khaneh Romantic — Exit popup: who sees it, what it offers, personal codes, counts
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Offers (تنظیمات ← پاپ‌آپ خروج):
 *   coupon   — shows a shared code; «استفاده از کد» puts it on the cart.
 *   personal — asks for a mobile; makes a one-use code for that number only (copied from a template
 *              coupon, valid N days), puts it on the cart, SMSes it and adds the number to the newsletter.
 *              The template coupon can stay inactive so its own code is useless.
 *   message  — just a title, a text and a button.
 * With items in the cart (and «اول یادآوری سبد» on), the popup leads with the cart: count, total, «تکمیل خرید».
 * Never on the cart, checkout, payment and account pages; never for the shop's staff or wholesale customers;
 * optionally only for guests or people who haven't ordered yet. Once per visitor every N days (kept in the
 * browser); a changed offer counts as new.
 */

namespace Modules\ExitPopup\Support;

use App\Core\Modules\Modules;
use App\Core\Settings\Settings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Checkout\Support\Cart;
use Modules\Content\Support\Newsletter;
use Modules\Coupon\Models\Coupon;
use Modules\Customer\Support\Mobile;
use Modules\Order\Models\Order;
use Modules\Sms\Support\Sms;
use Modules\Wholesale\Support\Wholesale;
use Throwable;

final class ExitPopup
{
    public const OFFERS = [
        'coupon' => 'کد تخفیف عمومی',
        'personal' => 'کد شخصی در ازای شماره موبایل (با پیامک)',
        'message' => 'فقط پیام و دکمه',
    ];

    public const PAGES = [
        'home' => 'صفحه‌ی اصلی', 'product' => 'صفحه‌ی محصول', 'category' => 'دسته‌ها و برندها',
        'shop' => 'فروشگاه و جستجو', 'blog' => 'مجله', 'other' => 'بقیه‌ی صفحه‌ها',
    ];

    public const AUDIENCES = ['all' => 'همه', 'no_orders' => 'کسانی که هنوز سفارش نداده‌اند', 'guests' => 'فقط کسانی که وارد نشده‌اند'];

    /** routes where leaving is part of buying (or private): never interrupt */
    private const NEVER = ['checkout.', 'order.', 'customer.', 'payment.', 'returns.', 'wholesale', 'filament.', 'exit-popup.'];

    public static function setting(string $key, mixed $default = null): mixed
    {
        return Settings::get('exit_popup.'.$key, $default);
    }

    /** What kind of page this route is (home, product…), or null for pages the popup never shows on. */
    public static function pageType(?string $route): ?string
    {
        if ($route === null) {
            return null;
        }
        foreach (self::NEVER as $prefix) {
            if (str_starts_with($route, $prefix)) {
                return null;
            }
        }

        return match (true) {
            $route === 'home' => 'home',
            $route === 'catalog.product' => 'product',
            in_array($route, ['catalog.category', 'catalog.brand'], true) => 'category',
            in_array($route, ['catalog.shop', 'catalog.search'], true) => 'shop',
            str_starts_with($route, 'content.blog'), $route === 'content.post' => 'blog',
            default => 'other',
        };
    }

    /** Should this page carry the popup? (decided on the server; how often is decided in the browser) */
    public function eligible(?string $route): bool
    {
        if (! self::setting('enabled', false)) {
            return false;
        }
        $type = self::pageType($route);
        if (! $type || ! in_array($type, (array) self::setting('pages', array_keys(self::PAGES)), true)) {
            return false;
        }
        if (Auth::guard('web')->check()) {   // staff browsing the site
            return false;
        }
        $customer = config('auth.guards.customer') ? Auth::guard('customer')->user() : null;
        if ($customer && Modules::enabled('Wholesale') && app(Wholesale::class)->current()) {
            return false;
        }

        return match (self::setting('audience', 'no_orders')) {
            'guests' => ! $customer,
            'no_orders' => ! $customer || ! Modules::enabled('Order') || ! Order::query()->where('customer_id', $customer->id)->exists(),
            default => true,
        } && ($this->offer() !== null || $this->cartReminder() !== null);
    }

    /** @return array{type: string, coupon: ?Coupon}|null the offer, or null when it can't be made (e.g. its coupon expired) */
    public function offer(): ?array
    {
        $type = (string) self::setting('offer', 'coupon');
        if ($type === 'message') {
            return ['type' => 'message', 'coupon' => null];
        }
        if (! Modules::enabled('Coupon') || ! ($coupon = Coupon::query()->find(self::setting('coupon_id')))) {
            return null;
        }
        if ($type === 'coupon' && (! $coupon->is_active || ($coupon->ends_at && $coupon->ends_at->isPast())
            || ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit))) {
            return null;
        }

        return ['type' => $type === 'personal' ? 'personal' : 'coupon', 'coupon' => $coupon];
    }

    /** @return array{count: int, total: int}|null */
    public function cartReminder(): ?array
    {
        if (! self::setting('cart_first', true)) {
            return null;
        }
        $cart = app(Cart::class);
        if ($cart->count() === 0) {
            return null;
        }

        return ['count' => $cart->count(), 'total' => $cart->subtotal()];
    }

    /** Changes when the offer changes, so visitors who closed the old one see the new one. */
    public static function version(): string
    {
        return substr(md5(json_encode([
            self::setting('offer'), self::setting('coupon_id'), self::setting('title'), self::setting('text'), self::setting('button_url'),
        ])), 0, 8);
    }

    // ── personal codes ─────────────────────────────────────────────────

    /**
     * A one-use code for this mobile (the same one again while it's still valid).
     *
     * @return array{coupon: Coupon, new: bool}|null null when the offer isn't personal / the number is invalid
     */
    public function personalCode(string $rawMobile, ?string $ip = null): ?array
    {
        $offer = $this->offer();
        $mobile = Mobile::normalize($rawMobile);
        if (! $mobile || ($offer['type'] ?? null) !== 'personal') {
            return null;
        }

        $previous = DB::table('exit_popup_leads')->where('mobile', $mobile)->whereNotNull('coupon_id')->latest('id')->value('coupon_id');
        $existing = $previous ? Coupon::query()->find($previous) : null;
        if ($existing) {   // one code per number: the same one again (the cart tells them if it's used or expired)
            return ['coupon' => $existing, 'new' => false];
        }

        $template = $offer['coupon'];
        $days = max(1, (int) self::setting('personal_days', 3));
        $coupon = $template->replicate(['used_count']);
        $coupon->fill([
            'code' => 'EX'.Coupon::generateCode(6),
            'description' => 'پاپ‌آپ خروج — '.$mobile,
            'allowed_mobiles' => [$mobile],
            'usage_limit' => 1,
            'usage_limit_per_customer' => 1,
            'starts_at' => null,
            'ends_at' => Carbon::now()->addDays($days)->endOfDay(),
            'is_active' => true,
        ]);
        $coupon->save();

        DB::table('exit_popup_leads')->insert(['mobile' => $mobile, 'coupon_id' => $coupon->id, 'ip' => $ip, 'created_at' => now()]);

        if (Modules::enabled('Content') && class_exists(Newsletter::class)) {
            Newsletter::subscribe($mobile, 'exit-popup');
        }
        $this->sms($mobile, $coupon, $days);

        return ['coupon' => $coupon, 'new' => true];
    }

    /** values: CODE (token), OFFER (token2: «۱۰٪ تا سقف…»), DAYS (token3) */
    private function sms(string $mobile, Coupon $coupon, int $days): void
    {
        $template = self::setting('sms_template');
        if (! $template || ! Modules::enabled('Sms')) {
            return;
        }
        try {
            app(Sms::class)->template($mobile, (string) $template, [$coupon->code, $coupon->summary(), (string) $days]);
        } catch (Throwable $e) {
            Log::warning('Exit popup SMS failed: '.$e->getMessage());
        }
    }

    // ── counts ─────────────────────────────────────────────────────────

    public static function hit(string $event): void
    {
        if (! in_array($event, ['shown', 'closed', 'converted'], true)) {
            return;
        }
        $day = now()->toDateString();
        DB::table('exit_popup_stats')->insertOrIgnore(['day' => $day]);
        DB::table('exit_popup_stats')->where('day', $day)->increment($event);
    }

    /** @return array{shown: int, closed: int, converted: int, leads: int} */
    public static function stats(int $days = 30): array
    {
        $from = now()->subDays($days - 1)->toDateString();
        $row = DB::table('exit_popup_stats')->where('day', '>=', $from)
            ->selectRaw('coalesce(sum(shown),0) as shown, coalesce(sum(closed),0) as closed, coalesce(sum(converted),0) as converted')->first();

        return [
            'shown' => (int) $row->shown, 'closed' => (int) $row->closed, 'converted' => (int) $row->converted,
            'leads' => DB::table('exit_popup_leads')->where('created_at', '>=', $from)->count(),
        ];
    }
}
