<?php

/**
 * Bulk prices: filters pick the models, percent/amount up/down with rounding, sales that stop being
 * discounts are removed, the admin page previews and applies, and a change can be undone.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\BulkEdit\Tests\Feature;

use App\Models\User;
use Livewire\Livewire;
use Modules\BulkEdit\Filament\Pages\BulkPrices;
use Modules\BulkEdit\Models\PriceChange;
use Modules\BulkEdit\Support\PriceChanger;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Search\Tests\Feature\SearchTestCase;

class BulkPricesTest extends SearchTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->catalog();
    }

    private function variant(string $sku): ProductVariant
    {
        return Product::query()->where('sku', $sku)->firstOrFail()->variants()->firstOrFail();
    }

    public function test_percent_and_amount_with_rounding_and_filters(): void
    {
        $changer = app(PriceChanger::class);

        // +10% on Goldkish only, rounded to 100,000 toman: 9,900,000 → 10,890,000 → 10,900,000 toman
        $changer->apply(['brands' => [$this->goldkish->id]], ['field' => 'price', 'direction' => 'up', 'mode' => 'percent', 'value' => 10, 'rounding' => 100000]);
        $this->assertSame(109_000_000, $this->variant('GK-7710')->price);
        $this->assertSame(28_000_000, $this->variant('GK-2801')->price);          // 2,500,000 + 10% = 2,750,000 → 2,800,000 toman
        $this->assertSame(29_500_000, $this->variant('3217073')->price);          // Riton: untouched

        // −500,000 toman on the decor category's sale prices only
        $changer->apply(['categories' => [$this->decor->id]], ['field' => 'sale', 'direction' => 'down', 'mode' => 'amount', 'value' => 500000, 'rounding' => 0]);
        $this->assertSame(17_000_000, $this->variant('3240112')->sale_price);     // 22,000,000 − 5,000,000

        // lowering the regular price below the sale price removes the sale
        $changer->apply(['search' => '3240112'], ['field' => 'price', 'direction' => 'down', 'mode' => 'percent', 'value' => 50, 'rounding' => 0]);
        $this->assertSame(13_700_000, $this->variant('3240112')->price);
        $this->assertNull($this->variant('3240112')->sale_price);

        $this->assertSame(3, PriceChange::query()->count());
    }

    public function test_undo_puts_back_only_untouched_models(): void
    {
        $changer = app(PriceChanger::class);
        $change = $changer->apply([], ['field' => 'price', 'direction' => 'up', 'mode' => 'percent', 'value' => 20, 'rounding' => 0]);
        $this->assertSame(4, $change->variants_count);

        $this->variant('GK-2801')->update(['price' => 31_000_000]);   // changed by hand afterwards
        $this->assertSame(3, $changer->undo($change));
        $this->assertSame(99_000_000, $this->variant('GK-7710')->price);
        $this->assertSame(31_000_000, $this->variant('GK-2801')->price);
        $this->assertSame(0, $changer->undo($change->fresh()));          // only once
    }

    public function test_admin_page_previews_and_applies(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get('/admin/bulk-prices')->assertOk()->assertSee('ویرایش گروهی قیمت');

        Livewire::test(BulkPrices::class)
            ->set('data.brands', [$this->riton->id])
            ->set('data.value', 5)
            ->assertSeeText('قیمت ۲ مدل از ۲ محصول عوض می‌شود')
            ->callAction('apply')
            ->assertNotified();

        $this->assertSame(31_000_000, $this->variant('3217073')->price);   // 2,950,000 toman +5% = 3,097,500 → 3,100,000 (rounded to 10,000)
        $this->assertSame(1, PriceChange::query()->count());

        Livewire::test(BulkPrices::class)->assertSee('افزایش ۵٪ قیمت اصلی')
            ->callAction('undo', arguments: ['change' => PriceChange::query()->value('id')]);
        $this->assertSame(29_500_000, $this->variant('3217073')->price);
    }
}
