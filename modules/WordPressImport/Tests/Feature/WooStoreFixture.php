<?php

/**
 * A small WooCommerce store (brand, categories, simple + variable products, image, post, page,
 * customer, coupon, reviews, HPOS orders) for importer tests.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\WordPressImport\Tests\Feature;

use Illuminate\Support\Facades\Storage;

trait WooStoreFixture
{
    protected FakeWordPress $wp;

    protected string $uploads;

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
        Storage::fake('local');
    }

    protected function site(bool $hpos = true): FakeWordPress
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

    protected function hposOrders(FakeWordPress $wp): void
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
}
