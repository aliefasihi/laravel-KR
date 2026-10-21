<?php

/**
 * Sets: shown on each member's page, one tap adds the whole set, the discount applies only to
 * complete sets; «bought together» comes from real orders.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Bundles\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Bundles\Livewire\ProductOffers;
use Modules\Bundles\Models\Bundle;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Product;
use Modules\Checkout\Support\Cart;
use Modules\Order\Models\Order;
use Tests\TestCase;

class BundlesTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, Product> */
    private array $p = [];

    protected function setUp(): void
    {
        parent::setUp();
        $brand = Brand::create(['name_fa' => 'گلدکیش', 'name_en' => 'Goldkish', 'slug' => 'goldkish']);
        foreach (['pot' => 50_000_000, 'ladle' => 5_000_000, 'mitt' => 3_000_000, 'cup' => 2_000_000] as $sku => $price) {
            $this->p[$sku] = Product::create(['brand_id' => $brand->id, 'sku' => $sku, 'name' => $sku, 'status' => Product::STATUS_ACTIVE]);
            $this->p[$sku]->variants()->create(['sku' => $sku.'-1', 'price' => $price, 'stock' => 10]);
        }
        $bundle = Bundle::create(['title' => 'ست آشپزی', 'discount_percent' => 10]);
        $bundle->items()->create(['product_id' => $this->p['pot']->id, 'quantity' => 1]);
        $bundle->items()->create(['product_id' => $this->p['mitt']->id, 'quantity' => 2]);
    }

    private function variant(string $sku): int
    {
        return $this->p[$sku]->variants()->first()->id;
    }

    public function test_the_whole_set_goes_to_the_cart_and_gets_its_discount(): void
    {
        $this->get($this->p['mitt']->url())->assertOk()->assertSee('ست پیشنهادی: ست آشپزی')->assertSee('افزودن ست به سبد');

        Livewire::test(ProductOffers::class, ['product' => $this->p['pot']])->call('addBundle', Bundle::query()->first()->id)->assertDispatched('cart-updated');
        $cart = app(Cart::class);
        $this->assertSame([1, 2], [$cart->quantityOf($this->variant('pot')), $cart->quantityOf($this->variant('mitt'))]);

        $quote = $cart->quote();
        $this->assertSame(5_600_000, $quote->discount);                     // 10% of (50M + 2×3M)
        $this->assertSame('تخفیف ست «ست آشپزی»', collect($quote->rows)->firstWhere('key', 'bundle-1')['label']);

        $cart->set($this->variant('mitt'), 1);                              // incomplete set: no discount
        $this->assertSame(0, app(Cart::class)->quote()->discount);
    }

    public function test_often_bought_together_comes_from_orders(): void
    {
        foreach ([1, 2] as $n) {
            $order = Order::query()->forceCreate(['number' => 'KR-'.$n, 'status' => 'completed', 'recipient_name' => 'x', 'recipient_mobile' => '09120000000', 'province' => 'تهران', 'city' => 'تهران', 'address' => 'x']);
            foreach (['ladle', 'cup'] as $sku) {
                $order->items()->create(['product_id' => $this->p[$sku]->id, 'variant_id' => $this->variant($sku), 'name' => $sku, 'regular_price' => 1, 'unit_price' => 1, 'quantity' => 1, 'total' => 1]);
            }
        }

        $this->get($this->p['ladle']->url())->assertSee('معمولاً با هم خریده می‌شوند');
        $this->get($this->p['pot']->url())->assertDontSee('معمولاً با هم خریده می‌شوند');   // never ordered with anything

        Livewire::test(ProductOffers::class, ['product' => $this->p['ladle']])->assertSet('picked', [$this->p['cup']->id])->call('addTogether');
        $this->assertSame(1, app(Cart::class)->quantityOf($this->variant('cup')));
        $this->assertSame(1, app(Cart::class)->quantityOf($this->variant('ladle')));
    }
}
