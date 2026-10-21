<?php

/**
 * Khaneh Romantic — Product page: «افزودن به لیست جهیزیه» (in every product page design's buy box)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Registry\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Catalog\Models\Product;
use Modules\Registry\Models\Registry;
use Modules\Registry\Models\RegistryItem;

class ProductButton extends Component
{
    #[Locked]
    public int $productId;

    public bool $added = false;

    public function mount(Product $product): void
    {
        $this->productId = $product->id;
        $this->added = ($registry = $this->registry()) !== null
            && $registry->items()->where('product_id', $product->id)->exists();
    }

    private function registry(): ?Registry
    {
        $id = Auth::guard('customer')->id();

        return $id ? Registry::query()->where('customer_id', $id)->latest('id')->first() : null;
    }

    public function add(): void
    {
        $registry = $this->registry();
        if (! $registry) {
            return;
        }
        RegistryItem::query()->firstOrCreate(
            ['registry_id' => $registry->id, 'product_id' => $this->productId],
            ['quantity' => 1, 'sort' => (int) $registry->items()->max('sort') + 1],
        );
        $this->added = true;
    }

    public function render()
    {
        return view('registry::livewire.product-button', ['registry' => $this->registry(), 'loggedIn' => Auth::guard('customer')->check()]);
    }
}
