<?php

/**
 * Gift wrap: the fee joins the totals everywhere, the order keeps the card message, staff see it.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\GiftWrap\Tests\Feature;

use App\Core\Settings\Settings;
use App\Models\User;
use Livewire\Livewire;
use Modules\Checkout\Support\Cart;
use Modules\GiftWrap\Livewire\GiftOption;
use Modules\GiftWrap\Models\OrderGift;
use Modules\Order\Filament\Resources\Orders\OrderResource;
use Modules\Order\Livewire\Checkout;
use Modules\Order\Models\Order;
use Modules\Order\Tests\Feature\ShopTestCase;

class GiftWrapTest extends ShopTestCase
{
    public function test_wrap_fee_message_and_the_order_keeps_them(): void
    {
        Settings::set(['gift.fee' => 500_000]);   // 50,000 toman
        $cart = $this->fillCart();
        $before = $cart->quote()->total();

        Livewire::test(GiftOption::class)->set('open', true)->set('wrap', true)->assertDispatched('cart-changed')
            ->set('message', 'تولدت مبارک مریم جان')->set('hide_prices', true);
        $this->assertSame($before + 500_000, $cart->quote()->total());
        $this->get('/cart')->assertSee('هدیه است؟');

        Livewire::test(Checkout::class)->set('shippingMethod', 'post')->set('paymentMethod', 'card')->call('place')->assertHasNoErrors();
        $order = Order::query()->sole();
        $this->assertSame(500_000, $order->fees_total);
        $gift = OrderGift::query()->sole();
        $this->assertSame(['تولدت مبارک مریم جان', true, true], [$gift->message, $gift->wrap, $gift->hide_prices]);
        $this->assertStringContainsString('کارت پیام', $order->notes()->pluck('body')->implode(' '));
        $this->assertFalse(app(Cart::class)->quote()->fees > 0);   // next cart starts clean

        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get(OrderResource::getUrl('view', ['record' => $order]))->assertOk()->assertSee('تولدت مبارک مریم جان')->assertSee('فاکتور قیمت‌دار داخل بسته نگذارید');
    }
}
