<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Checkout\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Checkout\Livewire\MiniCart;
use Modules\Checkout\Support\Cart;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $brand = Brand::create(['name_fa' => 'ریتون', 'name_en' => 'Riton', 'slug' => 'riton']);
        $product = Product::create(['brand_id' => $brand->id, 'sku' => 'RT-1', 'name' => 'ماهیتابه', 'status' => Product::STATUS_ACTIVE]);
        $this->variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'RT-1-28', 'price' => 2_000_000, 'stock' => 3]);
    }

    public function test_quantity_is_clamped_to_stock(): void
    {
        $cart = app(Cart::class);

        $this->assertSame(2, $cart->add($this->variant->id, 2));
        $this->assertSame(3, $cart->add($this->variant->id, 5));
        $this->assertSame(6_000_000, $cart->subtotal());
    }

    public function test_out_of_stock_lines_are_dropped(): void
    {
        $cart = app(Cart::class);
        $cart->add($this->variant->id);

        $this->variant->update(['stock' => 0]);

        $this->assertTrue($cart->lines()->isEmpty());
        $this->assertSame(0, $cart->count());
    }

    public function test_cart_page_renders(): void
    {
        $this->get('/cart')->assertOk()->assertSee('سبد خرید');
    }

    public function test_product_cards_add_through_the_single_mini_cart_listener(): void
    {
        Livewire::test(MiniCart::class)
            ->dispatch('cart-add', variantId: $this->variant->id)
            ->assertSet('open', true)
            ->assertDispatched('cart-added', variantId: $this->variant->id, added: true)
            ->assertDispatched('cart-count', count: 1);

        $this->assertSame(1, app(Cart::class)->quantityOf($this->variant->id));
    }

    public function test_home_has_no_livewire_component_per_product_card(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        // header mini-cart (+ search box) only — cards use plain Alpine buttons
        $this->assertLessThanOrEqual(3, substr_count($html, 'wire:snapshot'));
        $this->assertStringContainsString("cart-add', { variantId: {$this->variant->id} }", $html);
    }
}
