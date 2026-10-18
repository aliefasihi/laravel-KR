<?php

/**
 * Khaneh Romantic — Does a coupon apply to this cart, and how much does it take off?
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Product rules (WooCommerce-like): when products, categories or brands are listed, only lines
 * that match one of them count; excluded products/categories and (optionally) sale items never
 * count. The discount is taken from those lines only: percent of their sum (capped by
 * max_discount) or a fixed amount (at most their sum).
 */

namespace Modules\Coupon\Support;

use App\Core\Support\Money;
use App\Core\Support\PersianText;
use Closure;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Models\Category;
use Modules\Checkout\Support\CartLine;
use Modules\Checkout\Support\Pricing\QuoteContext;
use Modules\Coupon\Models\Coupon;

class CouponEvaluator
{
    /** How many earlier (not cancelled) orders a shopper has — provided by the Order module. */
    private static ?Closure $previousOrders = null;

    public static function countPreviousOrdersUsing(?Closure $callback): void
    {
        self::$previousOrders = $callback;
    }

    /** @return array{ok: bool, error: ?string, discount: int, free_shipping: bool} */
    public function evaluate(?Coupon $coupon, QuoteContext $context): array
    {
        $fail = fn (string $error) => ['ok' => false, 'error' => $error, 'discount' => 0, 'free_shipping' => false];
        $now = now();
        $subtotal = $context->subtotal();

        if (! $coupon || ! $coupon->is_active) {
            return $fail('این کد تخفیف معتبر نیست.');
        }
        if ($coupon->starts_at && $coupon->starts_at->isAfter($now)) {
            return $fail('این کد تخفیف هنوز فعال نشده است.');
        }
        if ($coupon->ends_at && $coupon->ends_at->isBefore($now)) {
            return $fail('مهلت استفاده از این کد تمام شده است.');
        }
        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            return $fail('ظرفیت استفاده از این کد تمام شده است.');
        }
        if ($coupon->min_subtotal && $subtotal < $coupon->min_subtotal) {
            return $fail('این کد برای خرید از '.Money::toman($coupon->min_subtotal).' تومان به بالاست.');
        }
        if ($coupon->max_subtotal && $subtotal > $coupon->max_subtotal) {
            return $fail('این کد برای خرید تا '.Money::toman($coupon->max_subtotal).' تومان است.');
        }

        $personal = $coupon->allowed_mobiles || $coupon->usage_limit_per_customer || $coupon->first_order_only;
        if ($personal && ! $context->customerId && ! $context->mobile) {
            return $fail('برای استفاده از این کد، اول وارد حساب کاربری شوید.');
        }
        if ($coupon->allowed_mobiles && ! in_array($context->mobile, $coupon->allowed_mobiles, true)) {
            return $fail('این کد برای حساب شما نیست.');
        }
        if ($coupon->usage_limit_per_customer && $this->usedBy($coupon, $context) >= $coupon->usage_limit_per_customer) {
            return $fail($coupon->usage_limit_per_customer === 1
                ? 'شما قبلاً از این کد استفاده کرده‌اید.'
                : 'شما '.PersianText::toPersianDigits($coupon->usage_limit_per_customer).' بار از این کد استفاده کرده‌اید.');
        }
        if ($coupon->first_order_only && self::$previousOrders && (self::$previousOrders)($context->customerId, $context->mobile) > 0) {
            return $fail('این کد فقط برای اولین خرید است.');
        }

        $eligible = $this->eligibleSubtotal($coupon, $context);
        if ($eligible === 0 && $coupon->amount > 0) {
            return $fail('این کد شامل کالاهای سبد شما نمی‌شود.');
        }

        $discount = match ($coupon->type) {
            'percent' => (int) floor($eligible * min(100, $coupon->amount) / 100),
            default => min($coupon->amount, $eligible),
        };
        if ($coupon->type === 'percent' && $coupon->max_discount) {
            $discount = min($discount, $coupon->max_discount);
        }
        $discount = intdiv($discount, 10) * 10; // whole Tomans

        return ['ok' => true, 'error' => null, 'discount' => $discount, 'free_shipping' => $coupon->free_shipping];
    }

    private function usedBy(Coupon $coupon, QuoteContext $context): int
    {
        return DB::table('coupon_usages')->where('coupon_id', $coupon->id)
            ->where(fn ($q) => $q
                ->when($context->customerId, fn ($w) => $w->orWhere('customer_id', $context->customerId))
                ->when($context->mobile, fn ($w) => $w->orWhere('mobile', $context->mobile)))
            ->count();
    }

    private function eligibleSubtotal(Coupon $coupon, QuoteContext $context): int
    {
        $includeCategories = $this->expand($coupon->category_ids);
        $excludeCategories = $this->expand($coupon->exclude_category_ids);
        $restricted = $coupon->product_ids || $coupon->category_ids || $coupon->brand_ids;

        return (int) $context->lines->sum(function (CartLine $line) use ($coupon, $includeCategories, $excludeCategories, $restricted) {
            $product = $line->variant->product;
            $categories = $product->relationLoaded('categories') ? $product->categories->modelKeys() : $product->categories()->pluck('categories.id')->all();

            if ($coupon->exclude_sale_items && $line->unitPrice() < $line->variant->price) {
                return 0;
            }
            if (in_array($product->id, $coupon->exclude_product_ids ?? []) || array_intersect($categories, $excludeCategories)) {
                return 0;
            }
            if ($restricted && ! (
                in_array($product->id, $coupon->product_ids ?? [])
                || array_intersect($categories, $includeCategories)
                || in_array($product->brand_id, $coupon->brand_ids ?? [])
            )) {
                return 0;
            }

            return $line->total();
        });
    }

    /** category ids + all their subcategories */
    private function expand(?array $ids): array
    {
        if (! $ids) {
            return [];
        }

        return Category::query()->whereIn('id', $ids)->get()
            ->flatMap(fn (Category $category) => $category->descendantIds())->unique()->values()->all();
    }
}
