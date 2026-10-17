<?php

/**
 * Khaneh Romantic — Demo catalog for local development (never run in production)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Mirrors the live site's category tree and URL paths so local pages look like the real store.
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
        $brands = collect([
            ['goldkish', 'گلدکیش', 'Goldkish'],
            ['riton', 'ریتون', 'Riton'],
            ['gala-home', 'گالا هوم', 'Gala Home'],
            ['beniko', 'بنیکو', 'Beniko'],
        ])->mapWithKeys(fn ($b) => [$b[0] => Brand::query()->updateOrCreate(['slug' => $b[0]], ['name_fa' => $b[1], 'name_en' => $b[2]])]);

        // [path, name, children[path => name]]
        $tree = [
            ['kitchen', 'آشپزخانه', [
                'cooking-dishes' => 'ظروف پخت و پز',
                'cooking-accessories' => 'ابزار آشپزی',
                'organizers' => 'نگهدارنده و نظم‌دهنده',
                'tea-coffee-maker' => 'تهیه چای و قهوه',
                'kitchen-textiles' => 'منسوجات آشپزخانه',
            ]],
            ['tableware', 'سرو و پذیرایی', [
                'dinnerware-sets' => 'سرویس غذاخوری',
                'servingware' => 'ظروف سرو و پذیرایی',
                'drinkware' => 'سرو نوشیدنی',
                'cutlery-serving-utensils' => 'قاشق چنگال و ابزار سرو',
            ]],
            ['home-decor', 'اکسسوری دکوراتیو', [
                'vases' => 'گلدان',
                'candle-holders' => 'شمعدان و جا شمعی',
                'decorative-clocks' => 'ساعت دکوراتیو',
                'wall-art' => 'تابلو و دیوارکوب',
            ]],
            ['lighting', 'نور و روشنایی', []],
            ['bathroom', 'حمام و سرویس بهداشتی', []],
            ['appliances', 'لوازم برقی', []],
        ];

        $cats = [];
        foreach ($tree as $i => [$path, $name, $children]) {
            $parent = Category::query()->updateOrCreate(['path' => $path], ['name' => $name, 'sort' => $i, 'is_active' => true, 'parent_id' => null]);
            $cats[$path] = $parent;
            $j = 0;
            foreach ($children as $childPath => $childName) {
                $cats["$path/$childPath"] = Category::query()->updateOrCreate(
                    ['path' => "$path/$childPath"],
                    ['name' => $childName, 'sort' => $j++, 'is_active' => true, 'parent_id' => $parent->id],
                );
            }
        }

        // [brand, sku, name, category, price (Rials), sale price, stock, sale ends in hours]
        $items = [
            ['riton', '3230260', 'بانکه شیشه‌ای درب‌دار کوچک شامپاینی طلایی ریتون مدل بلوط', 'kitchen/organizers', 38_000_000, 32_980_000, 7, 30],
            ['riton', '3217073', 'قندان سنگی کوچک طوسی نقره‌ای ریتون مدل بلوط', 'tableware/servingware', 29_500_000, 25_800_000, 4, 30],
            ['riton', '3216010', 'آجیل‌خوری سنگی طوسی ریتون مدل بلوط', 'tableware/servingware', 41_000_000, 34_500_000, 9, 30],
            ['riton', '3229011', 'شکلات‌خوری شیشه‌ای شامپاینی نقره‌ای ریتون', 'tableware/servingware', 33_200_000, null, 5, null],
            ['goldkish', 'GK646387', 'سرویس غذاخوری ۲۸ پارچه گلدکیش مدل سارینا', 'tableware/dinnerware-sets', 214_000_000, 189_000_000, 3, 54],
            ['goldkish', 'GK701137', 'پیاله گلدکیش مدل سارینا بسته ۶ عددی', 'tableware/servingware', 36_000_000, null, 12, null],
            ['goldkish', 'GK701139', 'بشقاب غذاخوری گلدکیش مدل سارینا مجموعه ۶ عددی', 'tableware/dinnerware-sets', 58_500_000, 49_700_000, 6, 54],
            ['goldkish', 'GK7710', 'سرویس قابلمه ۱۰ پارچه گرانیتی گلدکیش', 'kitchen/cooking-dishes', 99_000_000, 84_500_000, 6, 30],
            ['gala-home', 'GH2201', 'سرویس اوپال ۲۸ پارچه گالا هوم مدل هرمس', 'tableware/dinnerware-sets', 132_000_000, null, 2, null],
            ['gala-home', 'GH3105', 'گلدان شیشه‌ای رنگ طلایی گالا هوم', 'home-decor/vases', 32_000_000, 29_600_000, 8, 30],
            ['gala-home', 'GH4410', 'شمعدان فلزی طلایی دو شاخه گالا هوم', 'home-decor/candle-holders', 27_400_000, null, 0, null],
            ['beniko', 'BN1102', 'کتری قوری استیل بنیکو ۲.۵ لیتری', 'kitchen/tea-coffee-maker', 47_000_000, 41_900_000, 10, 54],
            ['beniko', 'BN2208', 'سرویس قاشق و چنگال ۳۰ پارچه بنیکو طلایی', 'tableware/cutlery-serving-utensils', 88_000_000, null, 4, null],
            ['beniko', 'BN3301', 'ساعت دیواری دکوراتیو بنیکو مدل رز', 'home-decor/decorative-clocks', 39_900_000, null, 6, null],
            ['riton', '3240112', 'فنجان و نعلبکی چینی ریتون مدل بلوط ۶ نفره', 'tableware/drinkware', 64_000_000, 55_000_000, 5, 54],
            ['goldkish', 'GK8801', 'ارگانایزر آشپزخانه دو طبقه گلدکیش', 'kitchen/organizers', 18_900_000, null, 15, null],
        ];

        foreach ($items as [$brand, $sku, $name, $cat, $price, $sale, $stock, $hours]) {
            $product = Product::query()->updateOrCreate(
                ['sku' => $sku],
                ['brand_id' => $brands[$brand]->id, 'name' => $name, 'status' => Product::STATUS_ACTIVE,
                    'short_description' => 'محصولی کاربردی و شیک برای آشپزخانه و پذیرایی؛ ترکیبی از زیبایی و دوام که حس گرم و دلنشینی به خانه می‌دهد.',
                    'description' => '<h2>معرفی محصول</h2><p>'.$name.' انتخابی کاربردی و زیبا برای خانه‌های امروزی است. طراحی هماهنگ با سبک‌های مدرن و نئوکلاسیک، آن را به گزینه‌ای مناسب برای چیدمان آشپزخانه، میز پذیرایی و تکمیل جهیزیه تبدیل کرده است.</p><h3>ویژگی‌ها</h3><ul><li>طراحی هماهنگ با چیدمان مدرن و کلاسیک</li><li>مناسب برای استفاده‌ی روزمره و پذیرایی</li><li>گزینه‌ای مناسب برای جهیزیه</li></ul>'],
            );
            $product->categories()->sync([$cats[$cat]->id => ['is_primary' => true]]);
            $product->variants()->updateOrCreate(['sku' => $sku.'-STD'], [
                'price' => $price,
                'sale_price' => $sale,
                'sale_ends_at' => $hours ? now()->addHours($hours) : null,
                'stock' => $stock,
            ]);
        }

        foreach ([
            ['how-to-choose-cookware', 'چطور قابلمه‌ی مناسب انتخاب کنیم؟', 'جنس، کف قابلمه و نوع اجاق؛ سه چیزی که قبل از خرید باید بدانید.'],
            ['dinner-table-styling', 'ایده‌های چیدمان میز شام برای مهمانی‌های خانوادگی', 'با چند ظرف سرو هماهنگ، شمع و گل، میز ساده را به میزی دلنشین تبدیل کنید.'],
            ['bone-china-vs-porcelain', 'بن‌چاینا یا چینی؟ تفاوت‌ها و نکته‌های نگهداری', 'هر آنچه برای انتخاب سرویس غذاخوری مناسب و نگهداری درست آن لازم است.'],
        ] as $i => [$slug, $title, $excerpt]) {
            Post::query()->updateOrCreate(['slug' => $slug], [
                'title' => $title,
                'excerpt' => $excerpt,
                'body_html' => '<p>'.$excerpt.'</p><h2>نکته‌ی اول</h2><p>متن نمونه برای توسعه‌ی قالب.</p>',
                'status' => Post::STATUS_PUBLISHED,
                'published_at' => now()->subDays($i + 1),
            ]);
        }
    }
}
