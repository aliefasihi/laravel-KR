<?php

/**
 * Old WooCommerce product URLs that nobody mapped: suggestion, approval, auto-301, "did you mean".
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Seo\Tests\Feature;

use Modules\Catalog\Models\Product;
use Modules\Search\Tests\Feature\SearchTestCase;
use Modules\Seo\Models\Redirect;
use Modules\Seo\Models\RedirectSuggestion;

class RedirectSuggestionTest extends SearchTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->catalog();
    }

    public function test_old_url_with_sku_is_suggested_and_shows_did_you_mean(): void
    {
        $this->get('/product/gk-sarina-gk7710-6/')
            ->assertNotFound()
            ->assertSee('شاید دنبال')
            ->assertSee('سرویس قابلمه ۱۰ پارچه گرانیتی');

        $suggestion = RedirectSuggestion::sole();
        $this->assertSame('sku', $suggestion->reason);
        $this->assertSame(1.0, $suggestion->score);
        $this->assertSame('/p/goldkish-gk-7710', $suggestion->to_path);
        $this->assertSame(RedirectSuggestion::PENDING, $suggestion->status);

        $this->get('/product/gk-sarina-gk7710-6/')->assertNotFound();
        $this->assertSame(2, $suggestion->fresh()->hits);
    }

    public function test_persian_slug_matches_by_words(): void
    {
        $this->get('/product/'.rawurlencode('قندان-سنگي-طوسي').'/')->assertNotFound();

        $suggestion = RedirectSuggestion::sole();
        $this->assertSame('words', $suggestion->reason);
        $this->assertSame(Product::where('sku', '3217073')->value('id'), $suggestion->product_id);
    }

    public function test_weak_matches_are_not_suggested(): void
    {
        $this->get('/product/yakhchal-sid-bai-sid/')->assertNotFound();

        $this->assertSame(0, RedirectSuggestion::count());
    }

    public function test_approving_creates_a_permanent_redirect(): void
    {
        $this->get('/product/gk-sarina-gk7710-6/');
        RedirectSuggestion::sole()->approve();

        $this->get('/product/gk-sarina-gk7710-6/')->assertStatus(301)->assertHeader('Location', url('/p/goldkish-gk-7710'));
        $this->assertSame('suggestion', Redirect::sole()->source);
    }

    public function test_rejected_suggestions_stay_rejected(): void
    {
        $this->get('/product/gk-sarina-gk7710-6/');
        RedirectSuggestion::sole()->reject();

        $this->get('/product/gk-sarina-gk7710-6/')->assertNotFound();
        $this->assertSame(RedirectSuggestion::REJECTED, RedirectSuggestion::sole()->status);
    }

    public function test_exact_sku_matches_can_redirect_automatically(): void
    {
        config(['modules.seo.auto_redirect_score' => 1.0]);

        $this->get('/product/gk-sarina-gk7710-6/')->assertStatus(301)->assertHeader('Location', url('/p/goldkish-gk-7710'));
        $this->assertSame(RedirectSuggestion::APPROVED, RedirectSuggestion::sole()->status);

        // a words-only match still waits for review
        $this->get('/product/'.rawurlencode('قندان-سنگی-طوسی').'/')->assertNotFound();
    }
}
