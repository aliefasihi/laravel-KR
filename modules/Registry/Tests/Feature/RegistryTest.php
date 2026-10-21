<?php

/**
 * Wedding list: the owner builds it from product pages, guests buy or reserve, items get ticked,
 * cancelled orders give the item back, private lists stay private.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Registry\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Product;
use Modules\Checkout\Support\Cart;
use Modules\Customer\Models\Customer;
use Modules\Order\Models\Order;
use Modules\Registry\Livewire\Manage;
use Modules\Registry\Livewire\ProductButton;
use Modules\Registry\Livewire\PublicList;
use Modules\Registry\Models\Registry;
use Modules\Registry\Models\RegistryItem;
use Tests\TestCase;

class RegistryTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private Customer $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $brand = Brand::create(['name_fa' => 'گلدکیش', 'name_en' => 'Goldkish', 'slug' => 'goldkish']);
        $this->product = Product::create(['brand_id' => $brand->id, 'sku' => 'G-1', 'name' => 'سرویس قابلمه', 'status' => Product::STATUS_ACTIVE]);
        $this->product->variants()->create(['sku' => 'G-1-1', 'price' => 90_000_000, 'stock' => 4]);
        $this->owner = Customer::create(['mobile' => '09121234567', 'first_name' => 'سارا']);
    }

    private function makeList(): Registry
    {
        Auth::guard('customer')->login($this->owner);
        Livewire::test(Manage::class)->set('title', 'جهیزیه‌ی سارا')->set('names', 'سارا و علی')->call('save')->assertHasNoErrors();
        Livewire::test(ProductButton::class, ['product' => $this->product])->call('add')->assertSet('added', true);
        Livewire::test(Manage::class)->call('setQuantity', RegistryItem::query()->first()->id, 2);
        Auth::guard('customer')->logout();

        return Registry::query()->firstOrFail();
    }

    public function test_guests_reserve_and_buy_and_items_get_ticked(): void
    {
        $registry = $this->makeList();
        $this->get($registry->url())->assertOk()->assertSee('جهیزیه‌ی سارا')->assertSee('سرویس قابلمه')->assertSee('noindex', false);

        Livewire::test(PublicList::class, ['registry' => $registry])
            ->set('reserving', RegistryItem::query()->first()->id)->set('name', 'خاله مریم')->call('reserve')->assertHasNoErrors();
        $this->assertSame(1, RegistryItem::query()->first()->load('claims')->remaining());

        // a guest buys the second one here: cart + order ticks it
        Livewire::test(PublicList::class, ['registry' => $registry])->call('buy', RegistryItem::query()->first()->id)->assertRedirect(route('checkout.cart'));
        $variant = $this->product->variants()->first();
        $this->assertSame(1, app(Cart::class)->quantityOf($variant->id));

        $order = Order::query()->forceCreate(['number' => 'KR-1', 'status' => 'processing', 'payment_status' => 'paid', 'customer_mobile' => '09350000000', 'customer_name' => 'عمو رضا', 'recipient_name' => 'عمو رضا', 'recipient_mobile' => '09350000000', 'province' => 'تهران', 'city' => 'تهران', 'address' => 'خیابان یک', 'subtotal' => 90_000_000, 'total' => 90_000_000]);
        $order->items()->create(['product_id' => $this->product->id, 'variant_id' => $variant->id, 'name' => 'سرویس قابلمه', 'regular_price' => 90_000_000, 'unit_price' => 90_000_000, 'quantity' => 1, 'total' => 90_000_000]);
        $payload = ['id' => $order->id, 'number' => 'KR-1', 'status' => 'processing', 'payment_status' => 'paid', 'customer_id' => null, 'mobile' => '09350000000', 'name' => 'عمو رضا', 'total' => 90_000_000, 'discount' => 0, 'coupon_code' => null, 'tracking_code' => null, 'notify' => false];
        Event::dispatch('order.placed', [$payload]);

        $item = RegistryItem::query()->first()->load('claims');
        $this->assertSame(0, $item->remaining());
        $this->get($registry->url())->assertSee('تهیه شد ✓');

        Event::dispatch('order.cancelled', [$payload]);                    // cancelled → needed again
        $this->assertSame(1, $item->fresh()->load('claims')->remaining());
    }

    public function test_private_lists_are_only_for_their_owner(): void
    {
        $registry = $this->makeList();
        $registry->update(['is_public' => false]);
        $this->get($registry->url())->assertNotFound();

        Auth::guard('customer')->login($this->owner);
        $this->get($registry->url())->assertOk();
        $this->get(route('customer.registry'))->assertOk()->assertSee('سرویس قابلمه')->assertSee($registry->url());
    }
}
