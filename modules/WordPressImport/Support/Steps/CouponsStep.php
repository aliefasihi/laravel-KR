<?php

/**
 * Khaneh Romantic — WooCommerce coupons
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * percent → percent; fixed_cart / fixed_product → a fixed amount on the cart (the closest rule here).
 * Product and category limits are mapped through the imported ids; the old use count is kept.
 */

namespace Modules\WordPressImport\Support\Steps;

use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Coupon\Models\Coupon;
use Modules\Customer\Support\Mobile;
use Modules\WordPressImport\Support\Report;
use Modules\WordPressImport\Support\Source;

class CouponsStep extends Step
{
    private const META = ['discount_type', 'coupon_amount', 'free_shipping', 'minimum_amount', 'maximum_amount', 'date_expires',
        'expiry_date', 'usage_limit', 'usage_limit_per_user', 'usage_count', 'product_ids', 'exclude_product_ids',
        'product_categories', 'exclude_product_categories', 'exclude_sale_items', 'customer_email'];

    public function key(): string
    {
        return 'coupons';
    }

    public function label(): string
    {
        return 'کدهای تخفیف';
    }

    public function requires(): array
    {
        return ['Coupon'];
    }

    public function sourceCount(): int
    {
        return $this->wp->table('posts')->where('post_type', 'shop_coupon')->whereIn('post_status', ['publish', 'draft', 'pending', 'future'])->count();
    }

    public function importedCount(): int
    {
        return Coupon::query()->whereNotNull('legacy_post_id')->count();
    }

    public function run(Report $report): void
    {
        $posts = $this->wp->table('posts')->where('post_type', 'shop_coupon')->whereIn('post_status', ['publish', 'draft', 'pending', 'future'])
            ->orderBy('ID')->when($report->limit, fn ($q, $l) => $q->limit($l))->get();
        $meta = $this->wp->postMeta($posts->pluck('ID')->map(fn ($i) => (int) $i)->all(), self::META);

        foreach ($posts as $post) {
            $m = $meta[(int) $post->ID] ?? [];
            $code = Coupon::normalize(html_entity_decode((string) $post->post_title));
            if ($code === '') {
                $report->count('coupons', 'skipped');

                continue;
            }
            $existing = Coupon::query()->where('legacy_post_id', $post->ID)->first() ?? Coupon::findByCode($code);
            $report->count('coupons', $existing ? 'updated' : 'created');
            if ($report->dryRun) {
                continue;
            }

            $percent = ($m['discount_type'] ?? 'fixed_cart') === 'percent';
            $amount = (float) ($m['coupon_amount'] ?? 0);
            $expires = $m['date_expires'] ?? null ?: ($m['expiry_date'] ?? null);
            $mobiles = collect((array) Source::unserialize($m['customer_email'] ?? []))
                ->map(fn ($e) => Mobile::normalize(strtok((string) $e, '@')))->filter()->values()->all();

            $coupon = $existing ?? new Coupon;
            $coupon->forceFill([
                'code' => $code,
                'description' => $post->post_excerpt ?: 'از ووکامرس',
                'type' => $percent ? 'percent' : 'fixed',
                'amount' => $percent ? (int) min(100, max(0, round($amount))) : (int) $this->rials($amount),
                'free_shipping' => ($m['free_shipping'] ?? 'no') === 'yes',
                'min_subtotal' => ($m['minimum_amount'] ?? '') !== '' ? $this->rials($m['minimum_amount']) : null,
                'max_subtotal' => ($m['maximum_amount'] ?? '') !== '' ? $this->rials($m['maximum_amount']) : null,
                'ends_at' => $expires ? $this->date($expires)?->setTimezone(config('khaneh.display_timezone', 'Asia/Tehran'))->endOfDay()->utc() : null,
                'usage_limit' => (int) ($m['usage_limit'] ?? 0) ?: null,
                'usage_limit_per_customer' => (int) ($m['usage_limit_per_user'] ?? 0) ?: null,
                'used_count' => (int) ($m['usage_count'] ?? 0),
                'exclude_sale_items' => ($m['exclude_sale_items'] ?? 'no') === 'yes',
                'product_ids' => $this->products($m['product_ids'] ?? '') ?: null,
                'exclude_product_ids' => $this->products($m['exclude_product_ids'] ?? '') ?: null,
                'category_ids' => $this->categories($m['product_categories'] ?? null) ?: null,
                'exclude_category_ids' => $this->categories($m['exclude_product_categories'] ?? null) ?: null,
                'allowed_mobiles' => $mobiles ?: null,
                'is_active' => $post->post_status === 'publish',
                'legacy_post_id' => (int) $post->ID,
                'created_at' => $this->date($post->post_date_gmt) ?? now(),
            ])->save();

            if (($m['discount_type'] ?? '') === 'fixed_product') {
                $report->info('coupons', "کد {$code} در ووکامرس «تخفیف ثابت هر محصول» بود؛ این‌جا تخفیف ثابت روی سبد است.");
            }
        }
    }

    /** "12,34" (products or variations) → our product ids */
    private function products(string $ids): array
    {
        $legacy = array_filter(array_map('intval', explode(',', $ids)));
        if (! $legacy) {
            return [];
        }

        return Product::withTrashed()->whereIn('legacy_post_id', $legacy)->pluck('id')
            ->merge(ProductVariant::query()->whereIn('legacy_post_id', $legacy)->pluck('product_id'))
            ->unique()->values()->all();
    }

    private function categories(mixed $value): array
    {
        $legacy = array_filter(array_map('intval', (array) Source::unserialize($value ?? [])));

        return $legacy ? Category::query()->whereIn('legacy_term_id', $legacy)->pluck('id')->all() : [];
    }
}
