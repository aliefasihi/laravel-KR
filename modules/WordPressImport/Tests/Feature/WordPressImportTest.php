<?php

/**
 * The WooCommerce importer end to end on a fake WordPress DB: catalog, posts, customers, coupons,
 * HPOS and classic orders, reviews, redirects, content links; idempotency, dry run, admin page.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\WordPressImport\Tests\Feature;

use App\Core\Support\UrlPath;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Attributes\Models\ProductAttribute;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Content\Models\Post;
use Modules\Coupon\Models\Coupon;
use Modules\Customer\Models\Customer;
use Modules\Media\Models\Media;
use Modules\Order\Models\Order;
use Modules\Reviews\Models\Review;
use Modules\Seo\Models\Redirect;
use Modules\WordPressImport\Filament\Pages\WordPressImportPage;
use Modules\WordPressImport\Jobs\RunImport;
use Modules\WordPressImport\Models\ImportRun;
use Modules\WordPressImport\Support\Importer;
use Modules\WordPressImport\Support\Report;
use Tests\TestCase;

class WordPressImportTest extends TestCase
{
    use RefreshDatabase;

    private FakeWordPress $wp;

    private string $uploads;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uploads = sys_get_temp_dir().'/wp-uploads-'.uniqid();
        @mkdir($this->uploads.'/2024/05', 0777, true);
        // 1×1 PNG saved under a .jpg name is enough for the importer
        file_put_contents($this->uploads.'/2024/05/pot.jpg', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='));
        config([
            'modules.wordpressimport.uploads_path' => $this->uploads,
            'modules.wordpressimport.legacy_base_url' => 'https://khanehromantic.com',
            'modules.wordpressimport.currency' => 'IRT',
        ]);
        Storage::fake('public');
    }

    private function site(bool $hpos = true): FakeWordPress
    {
        $wp = $this->wp = FakeWordPress::boot($hpos);
        $wp->option('siteurl', 'https://khanehromantic.com');
        $wp->option('permalink_structure', '/%postname%/');
        $wp->option('woocommerce_permalinks', ['product_base' => '/product', 'category_base' => 'product-category', 'tag_base' => 'product-tag']);
        $wp->option('wc_pwb_admin_tab_slug', 'brand');

        // taxonomies
        $wp->term(10, 'pwb-brand', 'گلدکیش', 'goldkish');
        $wp->term(20, 'product_cat', 'ظروف آشپزخانه', rawurlencode('ظروف-آشپزخانه'));
        $wp->term(21, 'product_cat', 'قابلمه', rawurlencode('قابلمه'), 20);
        $wp->term(30, 'product_type', 'simple', 'simple');
        $wp->term(31, 'product_type', 'variable', 'variable');
        $wp->db()->table('woocommerce_attribute_taxonomies')->insert(['attribute_name' => 'color', 'attribute_label' => 'رنگ']);
        $wp->term(40, 'pa_color', 'طلایی', 'talaei', 0, ['product_attribute_color' => '#d4af37']);
        $wp->term(41, 'pa_color', 'مشکی', 'black');
        $wp->term(50, 'product_tag', 'هدیه', rawurlencode('هدیه'));

        // image
        $wp->post(900, 'attachment', ['post_title' => 'قابلمه', 'guid' => 'https://khanehromantic.com/wp-content/uploads/2024/05/pot.jpg'], [
            '_wp_attached_file' => '2024/05/pot.jpg',
            '_wp_attachment_metadata' => ['file' => '2024/05/pot.jpg', 'sizes' => ['thumbnail' => ['file' => 'pot-300x300.jpg']]],
        ]);

        // simple product, on sale, managed stock, links to the variable one in its text
        $wp->post(100, 'product', [
            'post_title' => 'سرویس قابلمه ۱۰ پارچه', 'post_name' => rawurlencode('سرویس-قابلمه'),
            'post_content' => '<p>ببینید: <a href="https://khanehromantic.com/product/'.rawurlencode('تابه').'/">تابه</a></p>'
                .'<img src="https://khanehromantic.com/wp-content/uploads/2024/05/pot-300x300.jpg" srcset="https://khanehromantic.com/wp-content/uploads/2024/05/pot-300x300.jpg 300w" alt="x">',
        ], [
            '_sku' => 'GK-1024', '_regular_price' => '8450000', '_sale_price' => '7900000', '_price' => '7900000',
            '_manage_stock' => 'yes', '_stock' => '5', '_stock_status' => 'instock', '_thumbnail_id' => '900',
            'rank_math_primary_product_cat' => '21', 'rank_math_title' => '%title% %sep% %sitename%',
        ]);
        $wp->relate(100, 30, 21, 10);

        // variable product with two colours
        $wp->post(101, 'product', ['post_title' => 'تابه گرانیتی', 'post_name' => rawurlencode('تابه')], [
            '_product_attributes' => ['pa_color' => ['name' => 'pa_color', 'is_taxonomy' => 1, 'is_visible' => 1, 'is_variation' => 1]],
        ]);
        $wp->relate(101, 31, 20, 10, 40, 41, 50);
        $wp->post(102, 'product_variation', ['post_parent' => 101, 'post_title' => 'تابه - طلایی'], [
            'attribute_pa_color' => 'talaei', '_sku' => 'GK-7710-G', '_regular_price' => '2950000', '_stock_status' => 'instock',
        ]);
        $wp->post(103, 'product_variation', ['post_parent' => 101, 'post_title' => 'تابه - مشکی'], [
            'attribute_pa_color' => 'black', '_regular_price' => '2950000', '_stock_status' => 'outofstock',
        ]);

        // blog post linking to the simple product the old way; an about page
        $wp->post(200, 'post', ['post_title' => 'چطور قابلمه بخریم', 'post_name' => 'how-to-choose', 'post_content' => '<a href="/?p=100">قابلمه</a>']);
        $wp->post(300, 'page', ['post_title' => 'درباره ما', 'post_name' => rawurlencode('درباره-ما')]);

        // people
        $wp->user(500, 'ali', [
            'digits_phone' => '+989121112233', 'first_name' => 'علی', 'last_name' => 'فصیحی',
            'billing_first_name' => 'علی', 'billing_last_name' => 'فصیحی', 'billing_address_1' => 'خیابان ولیعصر', 'billing_city' => 'تهران',
            'billing_state' => 'THR', 'billing_postcode' => '۱۲۳۴۵۶۷۸۹۰', 'billing_phone' => '09121112233',
        ], '09121112233@digits.com');
        $wp->user(501, 'admin', ['billing_phone' => '09120000000'], 'admin@khanehromantic.com', 'administrator');

        // coupon
        $wp->post(600, 'shop_coupon', ['post_title' => 'yalda', 'post_excerpt' => 'یلدا'], [
            'discount_type' => 'percent', 'coupon_amount' => '10', 'usage_count' => '3', 'usage_limit_per_user' => '1',
            'date_expires' => (string) strtotime('2030-12-21 00:00:00 UTC'), 'product_categories' => [20], 'exclude_sale_items' => 'no',
        ]);

        // review + shop reply
        $wp->comment(800, 100, 'خیلی خوب بود', ['comment_author' => 'علی', 'user_id' => 500, 'comment_type' => 'review'], ['rating' => '4', 'verified' => '1']);
        $wp->comment(801, 100, 'ممنون از شما', ['comment_author' => 'خانه رمانتیک', 'user_id' => 501, 'comment_parent' => 800]);
        $wp->comment(802, 100, 'spam', ['comment_approved' => 'spam'], ['rating' => '1']);

        return $wp;
    }

    private function hposOrders(FakeWordPress $wp): void
    {
        // account holder, completed, coupon, shipping
        $wp->hposOrder(700, ['status' => 'wc-completed', 'total_amount' => 7160000, 'customer_id' => 500, 'payment_method' => 'zarinpal', 'payment_method_title' => 'زرین‌پال'],
            ['first_name' => 'علی', 'last_name' => 'فصیحی', 'phone' => '09121112233', 'address_1' => 'خیابان ولیعصر', 'city' => 'تهران', 'state' => 'THR', 'postcode' => '1234567890'],
            null,
            ['date_paid_gmt' => '2024-07-01 09:05:00', 'date_completed_gmt' => '2024-07-03 10:00:00', 'shipping_total_amount' => 50000, 'discount_total_amount' => 790000]);
        $wp->item(700, 'line_item', 'سرویس قابلمه ۱۰ پارچه', ['_product_id' => '100', '_variation_id' => '0', '_qty' => '1', '_line_subtotal' => '7900000', '_line_total' => '7110000']);
        $wp->item(700, 'shipping', 'پست پیشتاز', ['method_id' => 'flat_rate', 'cost' => '50000']);
        $wp->item(700, 'coupon', 'yalda', ['discount_amount' => '790000']);
        $wp->comment(850, 700, 'وضعیت از در حال پردازش به تکمیل شده تغییر کرد.', ['comment_type' => 'order_note']);

        // guest, two of a colour, half refunded
        $wp->hposOrder(701, ['status' => 'wc-processing', 'total_amount' => 5900000, 'customer_id' => 0, 'payment_method' => 'cod', 'payment_method_title' => 'پرداخت در محل'],
            ['first_name' => 'مریم', 'last_name' => 'احمدی', 'phone' => '۰۹۳۵۱۲۳۴۵۶۷', 'address_1' => 'خیابان زند', 'city' => 'شیراز', 'state' => 'FRS'],
            ['first_name' => 'مریم', 'last_name' => 'احمدی', 'phone' => '', 'address_1' => 'خیابان زند', 'city' => 'شیراز', 'state' => 'FRS'],
            ['shipping_total_amount' => 0, 'discount_total_amount' => 0]);
        $wp->item(701, 'line_item', 'تابه گرانیتی - طلایی', ['_product_id' => '101', '_variation_id' => '102', '_qty' => '2', '_line_subtotal' => '5900000', '_line_total' => '5900000', 'pa_color' => 'talaei']);
        $wp->db()->table('wc_orders')->insert(['id' => 702, 'status' => 'wc-completed', 'type' => 'shop_order_refund', 'total_amount' => -2950000, 'parent_order_id' => 701]);
    }

    private function import(array $steps = [], bool $dryRun = false): Report
    {
        return app(Importer::class)->run($steps, new Report($dryRun));
    }

    public function test_full_import_from_hpos_store(): void
    {
        $this->hposOrders($this->site());
        $report = $this->import();

        // brand, categories (exact path), attribute library
        $brand = Brand::query()->where('legacy_term_id', 10)->sole();
        $this->assertSame('goldkish', $brand->slug);
        $this->assertSame('ظروف-آشپزخانه/قابلمه', Category::query()->where('legacy_term_id', 21)->value('path'));
        $color = ProductAttribute::query()->where('name', 'رنگ')->sole();
        $this->assertSame('#d4af37', $color->values()->where('value', 'طلایی')->value('color'));

        // simple product: Toman → Rial, sale, stock, primary category, image
        $simple = Product::query()->where('legacy_post_id', 100)->with('variants', 'categories')->sole();
        $this->assertSame('goldkish-gk-1024', $simple->slug);
        $this->assertNull($simple->meta_title);                       // Rank Math template dropped
        $variant = $simple->variants->sole();
        $this->assertSame(84_500_000, (int) $variant->price);
        $this->assertSame(79_000_000, (int) $variant->sale_price);
        $this->assertSame(5, $variant->stock);
        $this->assertTrue((bool) $simple->categories->firstWhere('legacy_term_id', 21)->pivot->is_primary);
        $this->assertSame(1, $simple->media()->count());

        // variable product: options from pa_color, unmanaged stock
        $pan = Product::query()->where('legacy_post_id', 101)->with('variants')->sole();
        $this->assertSame('goldkish-wp101', $pan->slug);
        $gold = $pan->variants->firstWhere('legacy_post_id', 102);
        $this->assertSame(['رنگ' => 'طلایی'], $gold->options);
        $this->assertSame(50, $gold->stock);
        $this->assertSame(0, $pan->variants->firstWhere('legacy_post_id', 103)->stock);

        // post, customer (admin skipped, fake digits email dropped), address with Persian digits fixed
        $this->assertSame('how-to-choose', Post::query()->where('legacy_post_id', 200)->value('slug'));
        $ali = Customer::query()->where('mobile', '09121112233')->sole();
        $this->assertSame('u500', $ali->legacy_wp_id);
        $this->assertNull($ali->email);
        $this->assertSame('1234567890', $ali->addresses()->value('postal_code'));
        $this->assertSame('تهران', $ali->addresses()->value('province'));
        $this->assertFalse(Customer::query()->where('mobile', '09120000000')->exists());

        // coupon
        $coupon = Coupon::findByCode('YALDA');
        $this->assertSame('percent', $coupon->type);
        $this->assertSame(10, $coupon->amount);
        $this->assertSame(3, $coupon->used_count);
        $this->assertSame([Category::query()->where('legacy_term_id', 20)->value('id')], $coupon->category_ids);

        // HPOS order of an account holder: number kept, totals in Rials, coupon use, notes, no stock change
        $order = Order::query()->where('legacy_wp_id', 700)->with('items', 'notes')->sole();
        $this->assertSame('700', $order->number);
        $this->assertSame($ali->id, $order->customer_id);
        $this->assertSame('completed', $order->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame(71_600_000, $order->total);
        $this->assertSame(79_000_000, $order->subtotal);
        $this->assertSame(7_900_000, $order->discount);
        $this->assertSame(500_000, $order->shipping_total);
        $this->assertSame('YALDA', $order->coupon_code);
        $this->assertSame('پست پیشتاز', $order->shipping_method_title);
        $this->assertSame('wordpress', $order->source);
        $this->assertSame($variant->id, $order->items->sole()->variant_id);
        $this->assertTrue($order->notes->contains('body', 'وضعیت از در حال پردازش به تکمیل شده تغییر کرد.'));
        $this->assertSame(1, DB::table('coupon_usages')->where('order_id', $order->id)->count());
        $this->assertSame(5, $variant->fresh()->stock);

        // guest order: customer made from the mobile, refund, shipping falls back to billing phone
        $guest = Order::query()->where('legacy_wp_id', 701)->with('items')->sole();
        $maryam = Customer::query()->where('mobile', '09351234567')->sole();
        $this->assertSame($maryam->id, $guest->customer_id);
        $this->assertSame('فارس', $guest->province);
        $this->assertSame('09351234567', $guest->recipient_mobile);
        $this->assertSame(29_500_000, $guest->refunded_total);
        $this->assertSame('partially_refunded', $guest->payment_status);
        $this->assertSame(['رنگ' => 'طلایی'], $guest->items->sole()->options);

        // review with the shop's reply; spam skipped; stars on the product
        $review = Review::query()->where('legacy_comment_id', 800)->sole();
        $this->assertSame(4, $review->rating);
        $this->assertSame('ممنون از شما', $review->reply);
        $this->assertTrue($review->is_verified_buyer);
        $this->assertSame($ali->id, $review->customer_id);
        $this->assertSame(1, Review::query()->count());
        $this->assertEquals(4.0, (float) $simple->fresh()->rating_avg);

        // redirects: product base, ?p=, post permalink, page map, image sizes, tags
        $this->get('/product/'.rawurlencode('سرویس-قابلمه').'/')->assertStatus(301)->assertRedirect(url('p/goldkish-gk-1024'));
        $this->get('/?p=100')->assertStatus(301)->assertRedirect(url('p/goldkish-gk-1024'));
        $this->get('/how-to-choose/')->assertStatus(301)->assertRedirect(url('blog/how-to-choose'));
        $this->get('/'.rawurlencode('درباره-ما').'/')->assertStatus(301)->assertRedirect(url('aboutus'));
        $media = Media::query()->where('legacy_url_hash', UrlPath::hash('wp-content/uploads/2024/05/pot.jpg'))->sole();
        $imagePath = parse_url($media->url(), PHP_URL_PATH);
        $this->assertSame($imagePath, Redirect::query()->where('from_hash', UrlPath::hash('wp-content/uploads/2024/05/pot-300x300.jpg'))->value('to_path'));
        $this->assertStringStartsWith('/search?q=', Redirect::query()->where('from_hash', UrlPath::hash('product-tag/هدیه'))->value('to_path'));

        // content links rewritten, old srcset removed
        $html = $simple->fresh()->description;
        $this->assertStringContainsString('href="/p/goldkish-wp101"', $html);
        $this->assertStringContainsString('src="'.$imagePath.'"', $html);
        $this->assertStringNotContainsString('srcset', $html);
        $this->assertStringContainsString('href="/p/goldkish-gk-1024"', Post::query()->where('legacy_post_id', 200)->value('body_html'));

        $this->assertSame(0, collect($report->counts)->sum(fn ($c) => $c['failed'] ?? 0), json_encode($report->log, JSON_UNESCAPED_UNICODE));

        // running again changes nothing
        $this->import();
        $this->assertSame(2, Product::query()->count());
        $this->assertSame(2, Order::query()->count());
        $this->assertSame(2, Customer::query()->count());
        $this->assertSame(1, Review::query()->count());
        $this->assertSame(1, Media::query()->count());
        $this->assertSame(1, DB::table('coupon_usages')->count());
        $this->assertSame(1, Order::query()->where('legacy_wp_id', 700)->sole()->items()->count());
        $this->assertSame(2, $order->notes()->count());              // WooCommerce note + «منتقل شد», not doubled

        // the next new order doesn't take a number already kept from WooCommerce
        config(['modules.order.number_offset' => 698]);
        $next = Order::query()->create(['recipient_name' => 'x', 'recipient_mobile' => '09121112233', 'province' => 'تهران', 'city' => 'تهران', 'address' => 'x']);
        $next->assignNumber();
        $this->assertSame((string) (698 + $next->id + 1_000_000), $next->number);
    }

    public function test_classic_order_storage_and_dry_run(): void
    {
        $wp = $this->site(hpos: false);
        $wp->post(710, 'shop_order', ['post_status' => 'wc-on-hold', 'post_excerpt' => 'لطفاً کادو شود'], [
            '_customer_user' => '0', '_order_total' => '2950000', '_order_shipping' => '0', '_cart_discount' => '0',
            '_billing_first_name' => 'رضا', '_billing_phone' => '09125556677', '_billing_address_1' => 'میدان آزادی', '_billing_city' => 'تهران', '_billing_state' => 'THR',
            '_payment_method' => 'bacs', '_payment_method_title' => 'کارت به کارت',
        ]);
        $wp->item(710, 'line_item', 'تابه گرانیتی - طلایی', ['_product_id' => '101', '_variation_id' => '102', '_qty' => '1', '_line_subtotal' => '2950000']);

        // dry run: counts, writes nothing
        $report = $this->import(dryRun: true);
        $this->assertSame(1, $report->counts['orders']['created']);
        $this->assertSame(2, $report->counts['products']['created']);
        $this->assertSame(0, Product::query()->count());
        $this->assertSame(0, Order::query()->count());
        $this->assertSame(0, Redirect::query()->count());

        $this->import();
        $order = Order::query()->where('legacy_wp_id', 710)->sole();
        $this->assertSame('710', $order->number);
        $this->assertSame('on_hold', $order->status);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertSame(29_500_000, $order->total);
        $this->assertSame('لطفاً کادو شود', $order->customer_note);
        $this->assertSame('09125556677', $order->customer?->mobile);
        $this->assertSame('میدان آزادی', $order->address);
    }

    public function test_command_and_admin_page(): void
    {
        $this->site();

        $this->artisan('wp:import', ['steps' => ['taxonomies', 'products'], '--dry-run' => true])->assertSuccessful();
        $this->artisan('wp:import', ['steps' => ['nope']])->assertFailed();
        $this->assertSame(0, Product::query()->count());
        $this->artisan('wp:import', ['steps' => ['taxonomies', 'products'], '--limit' => 1])->assertSuccessful();
        $this->assertSame(1, Product::query()->count());
        $this->assertSame('done', ImportRun::query()->latest('id')->value('status'));

        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get('/admin/wordpress-import')->assertOk()->assertSee('متصل')->assertSee('سفارش‌ها: HPOS')->assertSee('مشتریان (کاربران وردپرس)');

        Queue::fake();
        Livewire::test(WordPressImportPage::class)
            ->set('selected', ['customers', 'orders'])
            ->set('dryRun', false)
            ->call('start');
        $run = ImportRun::query()->latest('id')->first();
        $this->assertSame(['customers', 'orders'], $run->steps);
        $this->assertFalse($run->dry_run);
        Queue::assertPushed(RunImport::class, fn ($job) => $job->run->is($run));

        // the job does the work and records it
        (new RunImport($run))->handle(app(Importer::class));
        $this->assertSame('done', $run->fresh()->status);
        $this->assertSame(1, Customer::query()->count());
        Livewire::test(WordPressImportPage::class)->assertSee('تمام شد');
    }
}
