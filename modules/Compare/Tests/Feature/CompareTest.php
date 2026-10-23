<?php

/**
 * Compare: the button on product pages (every design), the page side by side with specs rows,
 * «same» rows marked for «فقط تفاوت‌ها», drafts and junk ids ignored, at most 4.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Compare\Tests\Feature;

use Modules\Catalog\Models\Product;
use Modules\Search\Tests\Feature\SearchTestCase;

class CompareTest extends SearchTestCase
{
    public function test_button_and_page(): void
    {
        $this->catalog();
        $pot = Product::query()->where('sku', 'GK-7710')->first();
        $pan = Product::query()->where('sku', 'GK-2801')->first();
        $candy = Product::query()->where('sku', '3217073')->first();
        $draft = Product::query()->where('sku', '3240112')->first();
        $draft->update(['status' => Product::STATUS_DRAFT]);

        $this->get($pot->url())->assertOk()->assertSee('افزودن به مقایسه')->assertSee('$store.compare.toggle', false);

        $html = $this->get('/compare?ids='.$candy->id.',۹۹۹۹,'.$pot->id.','.$draft->id.','.$pan->id.',abc')->assertOk()
            ->assertSee('مقایسه‌ی محصولات')->assertSee('noindex', false)
            ->assertSee('فقط تفاوت‌ها')->assertSee('سرویس قابلمه ۱۰ پارچه گرانیتی')->assertDontSee('شمعدان فلزی طلایی')
            ->getContent();
        // in the order asked, brand row differs (Riton vs Goldkish) → always shown
        $this->assertLessThan(strpos($html, 'سرویس قابلمه ۱۰ پارچه'), strpos($html, 'قندان سنگی طوسی'));
        $this->assertMatchesRegularExpression('#<tr class="border-t border-line"\s*>\s*<th scope="row"[^>]*>برند#', $html);

        $this->get('/compare')->assertOk()->assertSee('هنوز محصولی برای مقایسه انتخاب نکرده‌اید');
    }
}
