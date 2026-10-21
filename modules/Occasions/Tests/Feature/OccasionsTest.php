<?php

/**
 * Occasions: the calendar picks the right one, the storefront (demo 1) is dressed for it, nothing
 * when off or on another demo, admins can preview, and the panel keeps one on at a time.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Occasions\Tests\Feature;

use App\Core\Settings\Settings;
use App\Core\Support\Jalali;
use App\Filament\Pages\SettingsPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Modules\Occasions\Support\Occasions;
use Tests\TestCase;

class OccasionsTest extends TestCase
{
    use RefreshDatabase;

    private function jalali(int $y, int $m, int $d): Carbon
    {
        [$gy, $gm, $gd] = Jalali::toGregorian($y, $m, $d);

        return Carbon::create($gy, $gm, $gd, 12, 0, 0, 'Asia/Tehran');
    }

    public function test_the_calendar_picks_the_occasion(): void
    {
        $this->assertSame('yalda', Occasions::byDate($this->jalali(1405, 9, 30)));
        $this->assertSame('nowruz', Occasions::byDate($this->jalali(1405, 1, 3)));
        $this->assertSame('sizdah', Occasions::byDate($this->jalali(1405, 1, 13)));
        $this->assertSame('sepandarmazgan', Occasions::byDate($this->jalali(1404, 11, 26)));
        $this->assertSame('halloween', Occasions::byDate(Carbon::create(2026, 10, 30, 12, 0, 0, 'Asia/Tehran')));
        $this->assertSame('blackfriday', Occasions::byDate(Carbon::create(2026, 11, 23, 12, 0, 0, 'Asia/Tehran')));   // (in 2026 روز مادر falls on Black Friday itself and wins)
        $this->assertSame('christmas', Occasions::byDate(Carbon::create(2026, 12, 25, 12, 0, 0, 'Asia/Tehran')));
        $this->assertNull(Occasions::byDate(Carbon::create(2026, 8, 20, 12, 0, 0, 'Asia/Tehran')));

        // the last Wednesday of Esfand 1404 (and the days before it) is چهارشنبه‌سوری, the next day نوروز
        $wednesday = collect(range(29, 20))->map(fn ($d) => $this->jalali(1404, 12, $d))->first(fn (Carbon $c) => $c->isWednesday());
        $this->assertSame('chaharshanbe', Occasions::byDate($wednesday));
        $this->assertSame('chaharshanbe', Occasions::byDate($wednesday->copy()->subDay()));
        $this->assertSame('nowruz', Occasions::byDate($wednesday->copy()->addDays(2)));

        // روز مادر comes from the lunar calendar: some days of the year are it
        $found = collect(range(0, 365))->map(fn ($i) => Carbon::create(2026, 1, 1, 12, 0, 0, 'Asia/Tehran')->addDays($i))
            ->filter(fn ($d) => Occasions::byDate($d) === 'mothers');
        $this->assertGreaterThanOrEqual(3, $found->count());
    }

    public function test_the_storefront_is_dressed_only_when_on(): void
    {
        $this->get('/')->assertOk()->assertDontSee('data-occasion', false)->assertDontSee('occ-config', false);

        Settings::set(['occasions.yalda' => true, 'occasions.yalda_promo' => 'شب یلدا با ما باشید']);
        $this->get('/')->assertOk()
            ->assertSee('data-occasion="yalda"', false)
            ->assertSee('شب یلدا با ما باشید')
            ->assertSee('یه فال حافظ بگیر', false)
            ->assertSee('occ-search-emblem', false)
            ->assertSee('id="occ-toggle"', false)
            ->assertSee('--color-rose: #c41e3a', false);

        // other demos: untouched for now
        Settings::set(['design.theme' => 'noir', 'design.chrome' => 'noir', 'design.home' => 'noir']);
        $this->get('/')->assertOk()->assertDontSee('data-occasion', false);
    }

    public function test_automatic_by_date_and_admin_preview(): void
    {
        Settings::set(['occasions.auto' => true]);
        $this->travelTo($this->jalali(1405, 9, 29));
        $this->get('/')->assertSee('data-occasion="yalda"', false);
        $this->travelTo(Carbon::create(2026, 8, 20, 12, 0, 0, 'Asia/Tehran'));
        $this->get('/')->assertDontSee('data-occasion', false);

        // a visitor can't preview; an admin can, and sees the strip
        $this->get('/?occasion=halloween')->assertDontSee('data-occasion', false);
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get('/?occasion=halloween')->assertSee('data-occasion="halloween"', false)->assertSee('پیش‌نمایش مناسبت');
        $this->get('/blog')->assertSee('data-occasion="halloween"', false);
        $this->get('/?occasion=live')->assertDontSee('data-occasion', false);
    }

    public function test_the_panel_keeps_one_occasion_on(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(SettingsPage::class)
            ->set('data.occasions.yalda', true)
            ->set('data.occasions.nowruz', true)
            ->assertSet('data.occasions.yalda', false)
            ->call('save');

        $this->assertTrue((bool) Settings::get('occasions.nowruz'));
        $this->assertFalse((bool) Settings::get('occasions.yalda'));
        $this->assertSame('nowruz', Occasions::active());
    }
}
