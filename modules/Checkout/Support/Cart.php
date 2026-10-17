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
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;

class Cart
{
    private const KEY = 'cart.items';

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

        return $qty;
    }

    public function remove(int $variantId): void
    {
        $this->set($variantId, 0);
    }

    public function clear(): void
    {
        $this->session->forget(self::KEY);
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

        return $lines;
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
