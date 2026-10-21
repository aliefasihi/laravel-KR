<?php

/**
 * Khaneh Romantic — Product page: «buy the set» and «often bought together»
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Bundles\Livewire;

use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Bundles\Support\Bundles;
use Modules\Catalog\Models\Product;
use Modules\Checkout\Support\Cart;

class ProductOffers extends Component
{
    #[Locked]
    public int $productId;

    /** @var list<int> «bought together» products ticked (this product is always included) */
    public array $picked = [];

    public ?string $notice = null;

    public function mount(Product $product): void
    {
        $this->productId = $product->id;
        $this->picked = app(Bundles::class)->together($product)->pluck('id')->all();
    }

    public function addBundle(int $bundleId, Cart $cart): void
    {
        $bundle = app(Bundles::class)->forProduct($this->product())->firstWhere('id', $bundleId);
        if (! $bundle) {
            $this->notice = 'این ست فعلاً کامل موجود نیست.';

            return;
        }
        foreach ($bundle->items as $item) {
            $variant = Bundles::offer($item->product, $item->quantity);
            $missing = $item->quantity - $cart->quantityOf($variant->id);
            if ($missing > 0) {
                $cart->add($variant->id, $missing);
            }
        }
        $this->notice = null;
        $this->dispatch('cart-updated');
    }

    public function addTogether(Cart $cart): void
    {
        $product = $this->product();
        $products = app(Bundles::class)->together($product)->whereIn('id', $this->picked)->prepend($product);
        foreach ($products as $item) {
            if (($variant = Bundles::offer($item)) && $cart->quantityOf($variant->id) < 1) {
                $cart->add($variant->id);
            }
        }
        $this->dispatch('cart-updated');
    }

    private function product(): Product
    {
        return Product::query()->with(['variants', 'media', 'brand'])->findOrFail($this->productId);
    }

    public function render()
    {
        $product = $this->product();
        $offers = app(Bundles::class);

        return view('bundles::livewire.product-offers', [
            'product' => $product,
            'bundles' => $offers->forProduct($product),
            'together' => $offers->together($product),
        ]);
    }
}
