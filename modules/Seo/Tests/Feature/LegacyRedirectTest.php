<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Seo\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Seo\Models\Redirect;
use Tests\TestCase;

class LegacyRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_persian_product_url_redirects_in_one_hop(): void
    {
        Redirect::point('/product/سرویس-قابلمه-گلدکیش/', '/p/goldkish-gk-1024', 'import');

        $this->get('/product/'.rawurlencode('سرویس-قابلمه-گلدکیش').'/')
            ->assertStatus(301)
            ->assertRedirect(url('p/goldkish-gk-1024'));

        $this->assertSame(1, Redirect::first()->hits);
    }

    public function test_wordpress_query_string_urls_redirect(): void
    {
        Redirect::point('?p=123', '/blog/how-to-choose-cookware', 'import');

        $this->get('/?p=123')->assertStatus(301)->assertRedirect(url('blog/how-to-choose-cookware'));
    }

    public function test_gone_urls_return_410(): void
    {
        Redirect::point('/tag/old-tag/', '/', 'import', 410);

        $this->get('/tag/old-tag/')->assertStatus(410);
    }

    public function test_unknown_urls_are_404(): void
    {
        $this->get('/nothing-here')->assertNotFound();
    }

    public function test_chains_are_collapsed(): void
    {
        Redirect::point('/a', '/b');
        Redirect::point('/b', '/c');

        $this->get('/a')->assertRedirect(url('c'));
        $this->assertSame(0, Redirect::query()->where('to_path', '/b')->count());
    }
}
