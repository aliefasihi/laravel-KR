<?php

/**
 * Khaneh Romantic — Session cart (variant id → quantity)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Prices are never stored in the session; they are read live from variants.
 * Quantities are clamped to stock on every write.
 */

namespace Modules\Checkout\Support;

use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Checkout\Support\Pricing\LinePrices;
use Modules\Checkout\Support\Pricing\Quote;
use Modules\Checkout\Support\Pricing\QuoteContext;
use Modules\Checkout\Support\Pricing\Quoter;
use WeakMap;

class Cart
{
    private const KEY = 'cart.items';

    /** lines already priced in this request (checkout asks several times), keyed by the request */
    private ?WeakMap $memo = null;

    public function __construct(private Session $session) {}

    /** Adds $qty of a variant; returns the quantity now in the cart. */
    public function add(int $variantId, int $qty = 1): int
    {
        return $this->set($variantId, $this->quantityOf($variantId) + max(1, $qty));
    }

    /** Sets an exact quantity (0 removes); returns the stored quantity. */
    public function set(int $variantId, int $qty): int
    {
        $items = $this->raw();
        $variant = $this->purchasable()->find($variantId);

        $qty = $variant ? min(max(0, $qty), max(0, $variant->stock)) : 0;

        if ($qty === 0) {
            unset($items[$variantId]);
        } else {
            $items[$variantId] = $qty;
        }

        $this->session->put(self::KEY, $items);
        Event::dispatch('cart.updated', [$items]);   // e.g. the abandoned-cart reminder keeps a copy for signed-in shoppers

        return $qty;
    }

    public function remove(int $variantId): void
    {
        $this->set($variantId, 0);
    }

    public function clear(): void
    {
        $this->session->forget([self::KEY, 'checkout.coupon']);
        Event::dispatch('cart.updated', [[]]);
    }

    /** variant id => quantity, as stored (no prices) */
    public function items(): array
    {
        return $this->raw();
    }

    public function quantityOf(int $variantId): int
    {
        return $this->raw()[$variantId] ?? 0;
    }

    /** Total number of units. */
    public function count(): int
    {
        return array_sum($this->raw());
    }

    /**
     * Cart lines with live prices. Lines whose variant disappeared or went
     * out of stock are dropped from the session.
     *
     * @return Collection<int, CartLine>
     */
    public function lines(): Collection
    {
        $items = $this->raw();

        if ($items === []) {
            return collect();
        }

        $this->memo ??= new WeakMap;
        $request = request();
        if (($hit = $this->memo[$request] ?? null) && $hit['items'] === $items) {
            return $hit['lines'];
        }

        $variants = $this->purchasable()
            ->with('product.brand', 'product.media')
            ->whereIn('id', array_keys($items))
            ->get()
            ->keyBy('id');

        $lines = collect($items)
            ->map(function (int $qty, int $id) use ($variants) {
                $variant = $variants->get($id);

                return $variant ? new CartLine($variant, min($qty, $variant->stock)) : null;
            })
            ->filter(fn (?CartLine $line) => $line !== null && $line->quantity > 0)
            ->values();

        $clean = $lines->mapWithKeys(fn (CartLine $line) => [$line->variant->id => $line->quantity])->all();

        if ($clean !== $items) {
            $this->session->put(self::KEY, $clean);
        }
        $lines = LinePrices::apply($lines);   // special prices for this shopper (e.g. wholesale)
        $this->memo[$request] = ['items' => $clean, 'lines' => $lines];

        return $lines;
    }

    /** Coupon code the shopper entered (kept in the session until the order is placed). */
    public function coupon(): ?string
    {
        return $this->session->get('checkout.coupon');
    }

    public function setCoupon(?string $code): void
    {
        $code === null || $code === ''
            ? $this->session->forget('checkout.coupon')
            : $this->session->put('checkout.coupon', $code);
    }

    /**
     * The cart priced by the pricing pipeline (coupon, shipping…).
     *
     * @param  array<string, mixed>  $choices  QuoteContext fields: province, city, shippingMethod, paymentMethod…
     */
    public function quote(array $choices = []): Quote
    {
        $customer = config('auth.guards.customer') ? auth('customer')->user() : null;

        return app(Quoter::class)->quote(new QuoteContext(...array_merge([
            'lines' => $this->lines(),
            'customerId' => $customer?->getAuthIdentifier(),
            'mobile' => $customer?->mobile,
            'couponCode' => $this->coupon(),
        ], $choices)));
    }

    /** Subtotal in Rials. */
    public function subtotal(): int
    {
        return $this->lines()->sum(fn (CartLine $line) => $line->total());
    }

    private function raw(): array
    {
        return array_map('intval', (array) $this->session->get(self::KEY, []));
    }

    private function purchasable()
    {
        return ProductVariant::query()
            ->where('is_active', true)
            ->whereHas('product', fn ($q) => $q->where('status', Product::STATUS_ACTIVE));
    }
}
