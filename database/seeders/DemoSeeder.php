<?php

/**
 * Khaneh Romantic — Demo catalog for local development (never run in production)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Content\Models\Post;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $goldkish = Brand::query()->updateOrCreate(['slug' => 'goldkish'], ['name_fa' => 'گلدکیش', 'name_en' => 'Goldkish']);
        $riton = Brand::query()->updateOrCreate(['slug' => 'riton'], ['name_fa' => 'ریتون', 'name_en' => 'Riton']);

        $categories = [];
        foreach (['قابلمه و تابه', 'سرویس غذاخوری', 'لوازم برقی', 'ظروف سرو', 'قوری و کتری', 'نظم‌دهنده‌ها'] as $i => $name) {
            $categories[] = Category::query()->updateOrCreate(
                ['path' => str_replace(' ', '-', $name)],
                ['name' => $name, 'sort' => $i, 'is_active' => true],
            );
        }

        $items = [
            [$goldkish, 'GK-1010', 'سرویس قابلمه ۱۰ پارچه گرانیتی', 0, 99_000_000, 84_500_000, 6],
            [$riton, 'RT-2028', 'ماهیتابه سرامیکی سایز ۲۸', 0, 21_800_000, null, 12],
            [$goldkish, 'GK-2026', 'سرویس غذاخوری ۲۶ پارچه چینی', 1, 127_000_000, null, 3],
            [$riton, 'RT-3025', 'کتری روگازی استیل ۲.۵ لیتری', 4, 17_500_000, 15_400_000, 0],
            [$goldkish, 'GK-4012', 'دیس سرو چینی بیضی', 3, 9_200_000, null, 20],
            [$riton, 'RT-5001', 'چای‌ساز برقی شیشه‌ای', 2, 46_000_000, null, 4],
        ];

        foreach ($items as [$brand, $sku, $name, $cat, $price, $sale, $stock]) {
            $product = Product::query()->updateOrCreate(
                ['sku' => $sku],
                ['brand_id' => $brand->id, 'name' => $name, 'status' => Product::STATUS_ACTIVE,
                    'short_description' => 'نمونه‌ی آزمایشی برای توسعه‌ی قالب.'],
            );
            $product->categories()->syncWithoutDetaching([$categories[$cat]->id => ['is_primary' => true]]);
            $product->variants()->updateOrCreate(['sku' => $sku.'-STD'], ['price' => $price, 'sale_price' => $sale, 'stock' => $stock]);
        }

        Post::query()->updateOrCreate(['slug' => 'how-to-choose-cookware'], [
            'title' => 'چطور قابلمه‌ی مناسب انتخاب کنیم؟',
            'excerpt' => 'جنس، کف قابلمه و نوع اجاق؛ سه چیزی که قبل از خرید باید بدانید.',
            'body_html' => '<h2>جنس قابلمه</h2><p>گرانیتی، استیل یا چدن؟ هر کدام برای نوع خاصی از پخت مناسب‌اند.</p>',
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
        ]);
    }
}
