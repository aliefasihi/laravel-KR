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

    public function test_one_part_can_differ_from_the_rest(): void
    {
        Settings::set(['design.card' => 'minimal']);
        $html = $this->get('/shop')->assertOk()->getContent();
        $this->assertStringContainsString('aspect-[4/5] overflow-hidden rounded-2xl bg-cream/70', $html);   // minimal card
        $this->assertStringNotContainsString('x-trap.noscroll="sheet"', $html);                            // classic listing
        $this->assertSame('classic-classic-minimal-classic', Designs::signature());
    }

    public function test_admins_preview_a_demo_visitors_never_see_it(): void
    {
        $this->get('/?design=demo2')->assertSee('هر روز، یک تجربه‌ی')->assertDontSee('پیش‌نمایش');   // a visitor: ignored

        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get('/?design=demo2')->assertSee('خانه‌ای گرم‌تر، هر روز')->assertSee('پیش‌نمایش');
        $this->get('/blog')->assertSee('>Journal<', false);              // the preview follows the admin around
        $this->get('/?design=off')->assertSee('هر روز، یک تجربه‌ی')->assertDontSee('فقط شما می‌بینید');
    }
}
