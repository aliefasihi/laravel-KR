<?php

/**
 * Switchable designs (demos): only the look changes, every page keeps working; admins preview
 * a demo before choosing it; each request gets exactly the design it should.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Tests\Feature;

use App\Core\Settings\Settings;
use App\Core\Support\Designs;
use App\Models\User;
use Modules\Catalog\Models\Product;
use Modules\Content\Models\Post;
use Modules\Search\Tests\Feature\SearchTestCase;

class DesignsTest extends SearchTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->catalog();
        Post::query()->create(['slug' => 'table-setting', 'title' => 'چیدمان میز شام', 'excerpt' => 'چند ایده‌ی ساده', 'body_html' => '<p>متن</p>', 'status' => Post::STATUS_PUBLISHED, 'published_at' => now()->subDay()]);
    }

    /** @return array<string, string> page => html */
    private function pages(): array
    {
        return [
            'home' => $this->get('/')->assertOk()->getContent(),
            'shop' => $this->get('/shop')->assertOk()->getContent(),
            'category' => $this->getRaw('/product-category/kitchen/')->assertOk()->getContent(),
            'search' => $this->get('/search?q=ماهیتابه')->assertOk()->getContent(),
            'blog' => $this->get('/blog')->assertOk()->getContent(),
            'post' => $this->get('/blog/table-setting')->assertOk()->getContent(),
            'product' => $this->get(Product::query()->firstOrFail()->url())->assertOk()->getContent(),
            'cart' => $this->get('/cart')->assertOk()->getContent(),   // a page no demo redesigns: only the palette changes
        ];
    }

    private function useDemo(string $demo): void
    {
        $slots = Designs::DEMOS[$demo]['slots'];
        Settings::set(['design.demo' => $demo] + array_combine(array_map(fn ($s) => 'design.'.$s, array_keys($slots)), $slots));
    }

    public function test_every_demo_renders_every_page(): void
    {
        $classic = $this->pages();
        $this->assertStringContainsString('هر روز، یک تجربه‌ی', $classic['home']);
        $this->assertStringContainsString('Romantic Journal', $classic['blog']);
        $this->assertStringNotContainsString('x-trap.noscroll="sheet"', $classic['shop']);

        $this->useDemo('demo2');
        $minimal = $this->pages();
        $this->assertStringContainsString('خانه‌ای گرم‌تر، هر روز', $minimal['home']);
        $this->assertStringContainsString('x-trap.noscroll="sheet"', $minimal['shop']);   // filters in a drawer
        $this->assertStringContainsString('x-trap.noscroll="sheet"', $minimal['category']);
        $this->assertStringContainsString('aspect-[4/5] overflow-hidden rounded-2xl bg-cream/70', $minimal['category']);   // minimal product card
        $this->assertStringContainsString('>Journal<', $minimal['blog']);
        $this->assertStringContainsString('BlogPosting', $minimal['post']);   // same structured data

        // back to demo 1: nothing of demo 2 is left behind (the view finder is reset per request)
        $this->useDemo('demo1');
        $again = $this->pages();
        $this->assertStringContainsString('هر روز، یک تجربه‌ی', $again['home']);
        $this->assertStringNotContainsString('x-trap.noscroll="sheet"', $again['shop']);
    }

    public function test_the_four_new_demos_render_every_page_with_their_own_look(): void
    {
        $markers = [
            'demo3' => ['theme' => 'noir', 'home' => 'هنر سفره‌آرایی', 'shop' => 'noir-filters', 'product' => 'noir-rule', 'blog' => 'The Journal'],
            'demo4' => ['theme' => 'pop', 'home' => 'خونه‌ت رو', 'shop' => 'همه‌ی فیلترها', 'product' => 'pop-frame', 'blog' => 'ایده بگیر'],
            'demo5' => ['theme' => 'arch', 'home' => 'آرام</span> گرفتن', 'shop' => 'rounded-t-[2.5rem] bg-cream', 'product' => 'arch-frame', 'blog' => 'یادداشت‌هایی از خانه'],
            'demo6' => ['theme' => 'bazaar', 'home' => 'همه‌چیز برای آشپزخانه و خانه', 'shop' => 'مرتب‌سازی:', 'product' => 'مشخصات فنی', 'blog' => 'تازه‌ترین مطالب'],
        ];
        foreach ($markers as $demo => $marker) {
            $this->useDemo($demo);
            $pages = $this->pages();
            foreach ($pages as $page => $html) {
                $this->assertStringContainsString('data-theme="'.$marker['theme'].'"', $html, "$demo $page: palette");
            }
            foreach (['home', 'shop', 'product', 'blog'] as $page) {
                $this->assertStringContainsString($marker[$page], $pages[$page], "$demo $page: its own template");
            }
            $this->assertStringContainsString('"@type":"Product"', $pages['product']);      // same structured data in every design
            $this->assertStringContainsString('BlogPosting', $pages['post']);
            $this->assertStringContainsString('noindex', $this->get('/shop?brand[]=riton')->getContent());   // same SEO rules
        }
    }

    public function test_one_part_can_differ_from_the_rest(): void
    {
        Settings::set(['design.card' => 'minimal']);
        $html = $this->get('/shop')->assertOk()->getContent();
        $this->assertStringContainsString('aspect-[4/5] overflow-hidden rounded-2xl bg-cream/70', $html);   // minimal card
        $this->assertStringNotContainsString('x-trap.noscroll="sheet"', $html);                            // classic listing
        $this->assertSame('classic-classic-classic-classic-minimal-classic-classic', Designs::signature());
    }

    public function test_admins_preview_a_demo_visitors_never_see_it(): void
    {
        $this->get('/?design=demo2')->assertSee('هر روز، یک تجربه‌ی')->assertDontSee('پیش‌نمایش');   // a visitor: ignored

        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get('/?design=demo2')->assertSee('خانه‌ای گرم‌تر، هر روز')->assertSee('پیش‌نمایش');
        $this->get('/blog')->assertSee('>Journal<', false);              // the preview follows the admin around
        $this->get('/?design=off')->assertSee('هر روز، یک تجربه‌ی')->assertDontSee('فقط شما می‌بینید');
    }

    public function test_each_demo_loads_only_its_own_stylesheet(): void
    {
        $this->withVite();
        $use = function (string $demo) {
            $slots = Designs::DEMOS[$demo]['slots'];
            Settings::set(array_combine(array_map(fn ($s) => 'design.'.$s, array_keys($slots)), $slots));

            return $this->get('/')->assertOk()->getContent();
        };

        $this->assertSame('resources/css/app.css', Designs::stylesheet());
        $this->assertMatchesRegularExpression('#assets/app-[\w-]+\.css#', $use('demo1'));

        $html = $use('demo3');
        $this->assertStringContainsString('build/assets/noir-', $html);
        $this->assertDoesNotMatchRegularExpression('#assets/app-[\w-]+\.css#', $html);   // instead of app.css, not on top of it
        $this->assertDoesNotMatchRegularExpression('#assets/(pop|arch|bazaar|minimal|all)-#', $html);

        $this->assertStringContainsString('build/assets/minimal-', $use('demo2'));

        // a hand-made mix of two demos: the combined stylesheet
        Settings::set(['design.card' => 'pop']);
        $this->assertStringContainsString('build/assets/all-', $this->get('/')->assertOk()->getContent());
    }

    public function test_switched_off_demos_leave_the_picker_and_the_preview(): void
    {
        Settings::set(['design.disabled' => ['demo4', 'demo5']]);
        $this->assertArrayNotHasKey('demo4', Designs::enabledDemos());
        $this->assertArrayHasKey('demo1', Designs::enabledDemos());
        $this->assertArrayNotHasKey('pop', Designs::enabledVariants('card'));
        $this->assertArrayHasKey('noir', Designs::enabledVariants('card'));

        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get('/?design=demo4')->assertDontSee('فقط شما می‌بینید');
        $this->get('/?design=demo3')->assertSee('فقط شما می‌بینید');
    }
}
