<?php

/**
 * Home sliders: built-in rows replaced, sources, slots, order and cache refresh.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\HomeSliders\Tests\Feature;

use App\Models\User;
use Livewire\Livewire;
use Modules\HomeSliders\Filament\Resources\HomeSliders\Pages\CreateHomeSlider;
use Modules\HomeSliders\Models\HomeSlider;
use Modules\Search\Tests\Feature\SearchTestCase;

class HomeSlidersTest extends SearchTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->catalog();
    }

    public function test_default_sliders_replace_the_built_in_rows(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        // the migration's two sliders: offers band (sale, by discount) and newest
        $this->assertSame(2, substr_count($html, 'aria-labelledby="home-slider-')); // (the brand tabs have rails of their own)
        $this->assertStringContainsString('محصولات<br class="max-lg:hidden"> شگفت‌انگیز', $html);
        $this->assertStringContainsString('id="home-slider-2"', $html);
        $this->assertStringNotContainsString('id="amazing-title"', $html); // built-in band gone
        $this->assertStringNotContainsString('id="new-title"', $html);     // built-in grid gone

        // the offers band only has sale products, the in-stock ones (only_in_stock)
        $band = substr($html, strpos($html, 'id="home-slider-1"'), strpos($html, 'id="home-slider-2"') - strpos($html, 'id="home-slider-1"'));
        $this->assertStringContainsString('سرویس قابلمه ۱۰ پارچه گرانیتی', $band);
        $this->assertStringContainsString('شمعدان فلزی طلایی دو شاخه', $band);
        $this->assertStringNotContainsString('قندان سنگی طوسی مدل بلوط', $band);
    }

    public function test_category_and_brand_sliders_in_their_slots(): void
    {
        HomeSlider::query()->update(['is_active' => false]);
        HomeSlider::create(['title' => 'آشپزخانه', 'accent' => 'دنیای', 'source' => 'category', 'category_id' => $this->kitchen->id,
            'only_in_stock' => false, 'slot' => 'before_journal', 'sort' => 1]);
        HomeSlider::create(['title' => 'ریتون', 'accent' => 'برند', 'source' => 'brand', 'brand_id' => $this->riton->id,
            'slot' => 'after_hero', 'sort' => 1]);

        $html = $this->get('/')->assertOk()->getContent();

        // brand slider (after the hero) comes before the category slider (before the journal)
        $this->assertLessThan(strpos($html, 'دنیای</span> آشپزخانه'), strpos($html, 'برند</span> ریتون'));

        // category = the category and its subcategories (incl. the sold-out pan: only_in_stock off)
        $kitchen = substr($html, strpos($html, 'دنیای</span> آشپزخانه'));
        foreach (['سرویس قابلمه ۱۰ پارچه گرانیتی', 'تابه گرانیتی سایز ۲۸', 'قندان سنگی طوسی مدل بلوط'] as $name) {
            $this->assertStringContainsString($name, $kitchen);
        }
        $this->assertStringContainsString('href="'.$this->kitchen->url().'"', $kitchen); // «مشاهده همه»
    }

    public function test_sliders_with_too_few_products_are_skipped_and_changes_show_at_once(): void
    {
        HomeSlider::query()->update(['is_active' => false]);
        $slider = HomeSlider::create(['title' => 'دکور', 'accent' => 'اکسسوری', 'source' => 'category', 'category_id' => $this->decor->id, 'slot' => 'after_categories']);

        $this->get('/')->assertOk()->assertDontSee('اکسسوری</span> دکور', false); // a single product: no slider

        $slider->update(['category_id' => $this->kitchen->id]);              // model event flushes the cached home
        $this->get('/')->assertOk()->assertSee('اکسسوری</span> دکور', false);
    }

    public function test_admin_creates_a_category_slider(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get('/admin/home-sliders')->assertOk()->assertSee('شگفت‌انگیز');

        Livewire::test(CreateHomeSlider::class)
            ->fillForm(['title' => 'پخت و پز', 'accent' => 'ظروف', 'source' => 'category', 'category_id' => $this->cookware->id,
                'sort_by' => 'price_asc', 'limit' => 12, 'only_in_stock' => false, 'slot' => 'after_brands', 'style' => 'glass', 'autoplay' => true, 'interval' => 5])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('home_sliders', ['title' => 'پخت و پز', 'category_id' => $this->cookware->id, 'interval' => 5]);
        $this->get('/')->assertSee('loopRail(5000, true)', false);
    }
}
