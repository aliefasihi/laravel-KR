<?php

/**
 * Khaneh Romantic — Product page: «save to wishlist» and «tell me when it's back»
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Sits in every product page design's buy box (hook product.buybox). Saving needs an account
 * (guests are sent to the login page and come back here); the stock request also works for
 * guests with just a mobile number.
 */

namespace Modules\Wishlist\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Catalog\Models\Product;
use Modules\Customer\Support\Mobile;
use Modules\Wishlist\Models\StockAlert;
use Modules\Wishlist\Models\WishlistItem;
use Modules\Wishlist\Support\Watchers;

class ProductActions extends Component
{
    #[Locked]
    public int $productId;

    public string $mobile = '';

    public bool $saved = false;

    public bool $requested = false;

    public ?string $error = null;

    public function mount(Product $product): void
    {
        $this->productId = $product->id;
        $customer = Auth::guard('customer')->user();
        if ($customer) {
            $this->mobile = (string) $customer->mobile;
            $this->saved = WishlistItem::query()->where('customer_id', $customer->id)->where('product_id', $product->id)->exists();
        }
        $this->requested = $this->mobile !== '' && StockAlert::query()->pending()->where('product_id', $product->id)->where('mobile', $this->mobile)->exists();
    }

    public function toggle()
    {
        $customer = Auth::guard('customer')->user();
        if (! $customer) {
            return $this->redirect(route('customer.login', ['back' => parse_url($this->product()->url(), PHP_URL_PATH)]));
        }

        $item = WishlistItem::query()->where('customer_id', $customer->id)->where('product_id', $this->productId)->first();
        if ($item) {
            $item->delete();
            $this->saved = false;
        } else {
            WishlistItem::query()->create([
                'customer_id' => $customer->id,
                'product_id' => $this->productId,
                'watched_price' => Watchers::offerPrice($this->product()->loadMissing('variants')),
            ]);
            $this->saved = true;
        }
    }

    public function notify(): void
    {
        $this->error = null;
        $mobile = Mobile::normalize($this->mobile);
        if (! $mobile) {
            $this->error = 'شماره موبایل درست نیست.';

            return;
        }
        if (RateLimiter::tooManyAttempts('stock-alert:'.request()->ip(), 10)) {
            $this->error = 'لطفاً کمی بعد دوباره امتحان کنید.';

            return;
        }
        RateLimiter::hit('stock-alert:'.request()->ip(), 3600);

        StockAlert::query()->firstOrCreate(
            ['product_id' => $this->productId, 'mobile' => $mobile, 'notified_at' => null],
            ['customer_id' => Auth::guard('customer')->id()],
        );
        $this->mobile = $mobile;
        $this->requested = true;
    }

    private function product(): Product
    {
        return Product::query()->findOrFail($this->productId);
    }

    public function render()
    {
        $product = $this->product()->loadMissing('variants');

        return view('wishlist::livewire.product-actions', [
            'inStock' => Watchers::offerPrice($product) !== null,
            'loggedIn' => Auth::guard('customer')->check(),
        ]);
    }
}
