<?php

/**
 * Khaneh Romantic — Wholesale prices and the wholesale customer flow
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Who: customers whose «همکاری» application was approved (or every applicant, if auto-approve is on).
 * What: a percentage below the regular price, by tiers on the quantity of one model in the cart
 *   (e.g. 1+ → 10%, 12+ → 15%). The tiers come from the most specific rule:
 *   product rule → closest category rule (the product's categories, then their parents…) → brand
 *   rule → the default tiers in تنظیمات ← فروش عمده. A rule without tiers = no wholesale price.
 * When: once the cart's retail total reaches the minimum order (if one is set). Below it, the
 *   cart says how much is left. A sale price that is already lower is kept.
 * The price is rounded (to 1,000 Toman by default) and becomes the cart line's unit price (Checkout
 * LinePrices), so orders, refunds and returns all use it. By default coupons and set discounts
 * don't stack on top of wholesale prices.
 */

namespace Modules\Wholesale\Support;

use App\Core\Modules\Modules;
use App\Core\Settings\Settings;
use App\Core\Support\PersianText;
use App\Core\Support\StoreCache;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Checkout\Support\CartLine;
use Modules\Customer\Models\Customer;
use Modules\Sms\Support\Sms;
use Modules\Wholesale\Models\WholesaleAccount;
use Modules\Wholesale\Models\WholesaleRule;
use Throwable;

final class Wholesale
{
    public const LABEL = 'قیمت همکار';

    /** @var array<int, ?WholesaleAccount> customer id => account (this request) */
    private array $accounts = [];

    /** @var array<int, list<array{min_qty: int, percent: float}>> product id => tiers (this request) */
    private array $tiers = [];

    // ── who ────────────────────────────────────────────────────────────

    public function account(?int $customerId): ?WholesaleAccount
    {
        if (! $customerId) {
            return null;
        }

        if (! array_key_exists($customerId, $this->accounts)) {
            $this->accounts[$customerId] = WholesaleAccount::query()->where('customer_id', $customerId)->first();
        }

        return $this->accounts[$customerId];
    }

    /** The logged-in customer's approved wholesale account, or null. */
    public function current(): ?WholesaleAccount
    {
        $id = config('auth.guards.customer') ? Auth::guard('customer')->id() : null;
        $account = $this->account($id);

        return $account?->isApproved() ? $account : null;
    }

    public function forget(?int $customerId = null): void
    {
        if ($customerId) {
            unset($this->accounts[$customerId]);
        } else {
            $this->accounts = [];
        }
    }

    // ── prices ─────────────────────────────────────────────────────────

    /** @return list<array{min_qty: int, percent: float}> sorted by quantity; [] = no wholesale price */
    public function tiersFor(Product $product): array
    {
        if (isset($this->tiers[$product->id])) {
            return $this->tiers[$product->id];
        }
        $rules = $this->rules();
        $tiers = $rules['product'][$product->id] ?? null;

        if ($tiers === null) {
            $tiers = $this->categoryTiers($product, $rules['category']);
        }
        if ($tiers === null && $product->brand_id) {
            $tiers = $rules['brand'][$product->brand_id] ?? null;
        }

        return $this->tiers[$product->id] = $tiers ?? self::clean((array) Settings::get('wholesale.tiers', []));
    }

    /** Wholesale unit price (Rials) for this many of one model, or null when there is none / it isn't lower. */
    public function priceFor(ProductVariant $variant, int $quantity = 1): ?int
    {
        $percent = null;
        foreach ($this->tiersFor($variant->product) as $tier) {
            if ($quantity >= $tier['min_qty']) {
                $percent = $tier['percent'];
            }
        }
        if (! $percent || ! $variant->price) {
            return null;
        }
        $step = max(0, (int) Settings::get('wholesale.rounding', 1000)) * 10;   // Toman → Rials
        $price = $variant->price * (100 - $percent) / 100;
        $price = (int) ($step > 0 ? round($price / $step) * $step : round($price));

        return $price > 0 && $price < $variant->currentPrice() ? $price : null;
    }

    /** @return list<array{min_qty: int, percent: float, price: ?int}> the ladder shown on the product page */
    public function ladder(ProductVariant $variant): array
    {
        return array_map(fn (array $tier) => $tier + ['price' => $this->priceFor($variant, $tier['min_qty'])], $this->tiersFor($variant->product));
    }

    /** Minimum retail total (Rials) for wholesale prices to apply; 0 = none. */
    public static function minimum(): int
    {
        return max(0, (int) Settings::get('wholesale.min_order', 0)) * 10;
    }

    /** @param  Collection<int, CartLine>  $lines  Rials still missing to reach the minimum (0 = reached) */
    public static function missing(Collection $lines): int
    {
        $retail = (int) $lines->sum(fn (CartLine $line) => $line->variant->currentPrice() * $line->quantity);

        return max(0, self::minimum() - $retail);
    }

    /**
     * Checkout LinePrices resolver: wholesale prices for the logged-in wholesale customer's lines.
     *
     * @param  Collection<int, CartLine>  $lines
     * @return array<int, array{price: int, label: string}>
     */
    public function linePrices(Collection $lines): array
    {
        if (! $this->current() || self::missing($lines) > 0) {
            return [];
        }
        $prices = [];
        foreach ($lines as $line) {
            if ($price = $this->priceFor($line->variant, $line->quantity)) {
                $prices[$line->variant->id] = ['price' => $price, 'label' => self::LABEL];
            }
        }

        return $prices;
    }

    // ── the application ────────────────────────────────────────────────

    /** @param  array<string, mixed>  $data */
    public function apply(Customer $customer, array $data, ?UploadedFile $document = null): WholesaleAccount
    {
        $account = WholesaleAccount::query()->firstOrNew(['customer_id' => $customer->id]);
        abort_if($account->exists && in_array($account->status, ['approved', 'suspended'], true), 403);

        $old = $account->document;
        $account->fill(array_intersect_key($data, array_flip(['shop_name', 'owner_name', 'business_type', 'province', 'city', 'phone', 'website', 'description'])));
        $account->business_type = array_key_exists($account->business_type, WholesaleAccount::TYPES) ? $account->business_type : 'other';
        $account->document = $document ? $document->store('wholesale', 'local') : $old;
        $account->admin_note = null;
        $auto = (bool) Settings::get('wholesale.auto_approve', false);
        $account->status = $auto ? 'approved' : 'pending';
        $account->approved_at = $auto ? now() : null;
        $account->save();
        $this->forget($customer->id);

        if ($auto) {
            $this->notify($account, 'approved');
        }

        return $account;
    }

    public function approve(WholesaleAccount $account, ?int $userId = null): void
    {
        $account->update(['status' => 'approved', 'approved_at' => now(), 'admin_note' => null, 'handled_by' => $userId]);
        $this->forget($account->customer_id);
        $this->notify($account, 'approved');
    }

    public function reject(WholesaleAccount $account, string $reason, ?int $userId = null): void
    {
        $account->update(['status' => 'rejected', 'admin_note' => $reason, 'handled_by' => $userId]);
        $this->forget($account->customer_id);
        $this->notify($account, 'rejected');
    }

    public function suspend(WholesaleAccount $account, ?string $reason = null, ?int $userId = null): void
    {
        $account->update(['status' => 'suspended', 'admin_note' => $reason, 'handled_by' => $userId]);
        $this->forget($account->customer_id);
    }

    // ── internals ──────────────────────────────────────────────────────

    /** values: SHOP (shop name; Kavenegar token), NOTE (rejection reason; token10) */
    private function notify(WholesaleAccount $account, string $step): void
    {
        $template = Settings::get('wholesale.'.$step.'_template');
        $mobile = $account->customer?->mobile;
        if (! $template || ! $mobile || ! Modules::enabled('Sms')) {
            return;
        }
        try {
            app(Sms::class)->template($mobile, (string) $template, [
                mb_substr($account->shop_name, 0, 40),
                mb_substr((string) ($account->admin_note ?: '-'), 0, 80),
            ]);
        } catch (Throwable $e) {
            Log::warning('Wholesale SMS failed: '.$e->getMessage());
        }
    }

    /** @return array{product: array<int, list>, category: array<int, list>, brand: array<int, list>} */
    private function rules(): array
    {
        return StoreCache::remember('wholesale.rules', function () {
            $rules = ['product' => [], 'category' => [], 'brand' => []];
            foreach (WholesaleRule::query()->where('is_active', true)->get() as $rule) {
                if (isset($rules[$rule->scope])) {
                    $rules[$rule->scope][$rule->target_id] = self::clean((array) $rule->tiers);
                }
            }

            return $rules;
        });
    }

    /** The rule of the closest category: the product's own categories first, then their parents… */
    private function categoryTiers(Product $product, array $categoryRules): ?array
    {
        if ($categoryRules === []) {
            return null;
        }
        $parents = StoreCache::remember('wholesale.category_parents', fn () => Category::query()->pluck('parent_id', 'id')->all());
        $level = DB::table('category_product')->where('product_id', $product->id)->pluck('category_id')->all();
        $seen = [];

        while ($level !== []) {
            $found = array_values(array_filter(array_map(fn ($id) => $categoryRules[$id] ?? null, $level), fn ($t) => $t !== null));
            if ($found !== []) {
                // two categories at the same distance: the better deal for the customer
                usort($found, fn ($a, $b) => ($b[0]['percent'] ?? 0) <=> ($a[0]['percent'] ?? 0));

                return $found[0];
            }
            $seen += array_flip($level);
            $level = array_values(array_unique(array_filter(
                array_map(fn ($id) => $parents[$id] ?? null, $level),
                fn ($id) => $id !== null && ! isset($seen[$id]),
            )));
        }

        return null;
    }

    /** 12.5 → «۱۲٫۵» (no trailing zeros) */
    public static function percent(float $percent): string
    {
        return str_replace('.', '٫', PersianText::toPersianDigits(rtrim(rtrim(number_format($percent, 1, '.', ''), '0'), '.')));
    }

    /** @return list<array{min_qty: int, percent: float}> */
    public static function clean(array $tiers): array
    {
        $clean = [];
        foreach ($tiers as $tier) {
            $qty = max(1, (int) ($tier['min_qty'] ?? 1));
            $percent = min(90, max(0, (float) ($tier['percent'] ?? 0)));
            if ($percent > 0) {
                $clean[$qty] = ['min_qty' => $qty, 'percent' => $percent];
            }
        }
        ksort($clean);

        return array_values($clean);
    }
}
