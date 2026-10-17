<?php

/**
 * Khaneh Romantic — Variant picker + add to cart (product page)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Checkout\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Checkout\Support\Cart;

class AddToCart extends Component
{
    #[Locked]
    public int $productId;

    public ?int $variantId = null;

    public int $quantity = 1;

    public ?string $message = null;

    public function mount(Product $product): void
    {
        $this->productId = $product->id;

        $this->variantId = $this->variants->firstWhere(fn (ProductVariant $v) => $v->stock > 0)?->id
            ?? $this->variants->first()?->id;
    }

    /** @return Collection<int, ProductVariant> */
    #[Computed]
    public function variants(): Collection
    {
        return ProductVariant::query()
            ->where('product_id', $this->productId)
            ->where('is_active', true)
            ->orderBy('price')
            ->get();
    }

    #[Computed]
    public function selected(): ?ProductVariant
    {
        return $this->variants->firstWhere('id', $this->variantId);
    }

    public function updatedVariantId(): void
    {
        $this->quantity = 1;
        $this->message = null;
    }

    public function increment(): void
    {
        $this->quantity = min($this->quantity + 1, max(1, $this->selected?->stock ?? 1));
    }

    public function decrement(): void
    {
        $this->quantity = max(1, $this->quantity - 1);
    }

    public function add(Cart $cart): void
    {
        $variant = $this->selected;

        if ($variant === null || $variant->stock < 1) {
            $this->message = 'این مدل در حال حاضر موجود نیست.';

            return;
        }

        $before = $cart->quantityOf($variant->id);
        $after = $cart->add($variant->id, $this->quantity);

        $this->message = $after > $before
            ? 'به سبد خرید اضافه شد.'
            : 'به سقف موجودی این مدل رسیده‌اید.';

        $this->dispatch('cart-updated');
    }

    public function render(): View
    {
        return view('checkout::livewire.add-to-cart');
    }
}
