<?php

/**
 * Reviews: login, moderation, verified buyers, stars on cards, schema, admin.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Reviews\Tests\Feature;

use App\Core\Settings\Settings;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Modules\Catalog\Models\Product;
use Modules\Order\Livewire\Checkout;
use Modules\Order\Models\Order;
use Modules\Order\Tests\Feature\ShopTestCase;
use Modules\Reviews\Filament\Resources\Reviews\Pages\ListReviews;
use Modules\Reviews\Livewire\ReviewForm;
use Modules\Reviews\Models\Review;

class ReviewsTest extends ShopTestCase
{
    private Product $pan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pan = Product::query()->where('sku', 'GK-7710')->firstOrFail();
    }

    public function test_guests_are_asked_to_log_in_and_come_back(): void
    {
        Auth::guard('customer')->logout();

        $this->get($this->pan->url())->assertOk()
            ->assertSee('href="#reviews"', false)
            ->assertSee('هنوز نظری ثبت نشده است.')
            ->assertSee('ورود و نوشتن نظر');

        $this->get('/account/login?back='.urlencode('/p/goldkish-gk-7710#reviews'))->assertOk();
        $this->assertSame(url('/p/goldkish-gk-7710#reviews'), session('url.intended'));
        $this->get('/account/login?back='.urlencode('//evil.example'));
        $this->assertSame(url('/p/goldkish-gk-7710#reviews'), session('url.intended')); // ignored
    }

    public function test_moderation_stars_and_schema(): void
    {
        Livewire::test(ReviewForm::class, ['productId' => $this->pan->id])
            ->set('rating', 0)->set('body', 'کوتاه')->call('submit')->assertHasErrors(['rating', 'body'])
            ->set('rating', 4)->set('title', 'خوب')->set('body', 'کیفیت پوشش گرانیتی عالی است و غذا نمی‌چسبد.')->call('submit')
            ->assertHasNoErrors()->assertSet('sent', true);

        $review = Review::query()->sole();
        $this->assertSame('pending', $review->status);
        $this->assertFalse($review->is_verified_buyer);
        $this->get($this->pan->url())->assertDontSee('غذا نمی‌چسبد');

        $review->update(['status' => 'approved', 'reply' => 'ممنون از شما']);
        $this->pan->refresh();
        $this->assertSame(1, $this->pan->rating_count);
        $this->assertEquals(4.0, (float) $this->pan->rating_avg);

        $html = $this->get($this->pan->url())->assertOk()
            ->assertSee('غذا نمی‌چسبد')->assertSee('پاسخ خانه رمانتیک')->assertSee('نظرات (۱)')
            ->getContent();
        $this->assertStringContainsString('"aggregateRating":{"@type":"AggregateRating","ratingValue":4,"reviewCount":1', $html);

        // the stars on product cards (cached pages are refreshed)
        $this->getRaw('/product-category/kitchen/')->assertSee('امتیاز 4.0 از ۵', false);
    }

    public function test_verified_buyers_can_be_published_automatically(): void
    {
        Settings::set(['reviews.auto_approve' => 'verified']);
        $this->fillCart();
        Livewire::test(Checkout::class)->set('shippingMethod', 'post')->set('paymentMethod', 'cod')->call('place');
        $this->assertSame('processing', Order::query()->sole()->status);

        Livewire::test(ReviewForm::class, ['productId' => $this->pan->id])
            ->set('rating', 5)->set('body', 'عالی بود، دقیقاً مثل عکس.')->call('submit')->assertHasNoErrors();

        $review = Review::query()->sole();
        $this->assertTrue($review->is_verified_buyer);
        $this->assertSame('approved', $review->status);
        $this->get($this->pan->url())->assertSee('خریدار این محصول');
    }

    public function test_admin_moderation(): void
    {
        $review = Review::create(['product_id' => $this->pan->id, 'customer_id' => $this->customer->id, 'name' => 'علی', 'rating' => 2, 'body' => 'بسته‌بندی ضعیف بود و دیر رسید.']);
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->get('/admin/reviews')->assertOk()->assertSee('بسته‌بندی ضعیف');
        Livewire::test(ListReviews::class)
            ->callTableAction('reply', $review, ['reply' => 'عذرخواهی می‌کنیم؛ پیگیری شد.'])
            ->assertHasNoTableActionErrors();

        $review->refresh();
        $this->assertSame('approved', $review->status);
        $this->assertSame('عذرخواهی می‌کنیم؛ پیگیری شد.', $review->reply);
        $this->assertSame(1, $this->pan->fresh()->rating_count);
    }
}
