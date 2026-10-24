<?php

/**
 * The admin search box finds menu pages, settings tabs and the fields inside them, and still finds records.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Tests\Feature;

use App\Core\Settings\Settings;
use App\Filament\Support\AdminSearch;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Livewire\GlobalSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Customer\Models\Customer;
use Tests\TestCase;

class AdminSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get('/admin');   // boots the panel (navigation, settings tabs)
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    /** @return array<string, list<array{title: string, url: string}>> */
    private function search(string $query): array
    {
        $results = (new AdminSearch)->getResults($query);

        return $results->getCategories()
            ->map(fn ($items) => collect($items)->map(fn ($r) => ['title' => (string) $r->title, 'url' => $r->url])->values()->all())
            ->all();
    }

    public function test_settings_tabs_and_fields_are_found_with_persian_variants(): void
    {
        $sms = $this->search('پیامك');   // Arabic ك
        $this->assertArrayHasKey('تنظیمات', $sms);
        $this->assertContains('پیامک', array_column($sms['تنظیمات'], 'title'));
        $this->assertStringContainsString('tab=sms', $sms['تنظیمات'][0]['url']);

        $this->assertNotEmpty($this->search('sms')['تنظیمات'] ?? []);              // English key
        $this->assertNotEmpty($this->search('زرین پال')['تنظیمات'] ?? []);          // half-space / spacing

        // every tab has readable fields, and a field result opens its tab at that field
        $withFields = collect(Settings::sections())->filter(fn ($s) => AdminSearch::fields($s) !== []);
        $this->assertGreaterThan(count(Settings::sections()) / 2, $withFields->count());

        [$name, $label] = AdminSearch::fields(Settings::sections()['sms'])[0];
        $hit = collect($this->search($label)['تنظیمات'] ?? [])->firstWhere('title', $label);
        $this->assertNotNull($hit, "field «{$label}» not found");
        $this->assertStringContainsString('field='.urlencode($name), $hit['url']);

        $this->get($hit['url'])->assertOk()->assertSee('scrollIntoView', false);
    }

    public function test_menu_pages_and_records(): void
    {
        $pages = $this->search('سفارش');
        $this->assertNotEmpty($pages['صفحه‌های پنل'] ?? []);

        Customer::create(['mobile' => '09121112233', 'first_name' => 'نگار']);
        $this->assertNotEmpty(collect($this->search('09121112233'))->except(['صفحه‌های پنل', 'تنظیمات'])->flatten(1)->all());

        $this->assertSame([], $this->search(' '));
    }

    public function test_the_top_bar_box_uses_it(): void
    {
        Livewire::test(GlobalSearch::class)->set('search', 'پیامک')->assertSee('تنظیمات')->assertSee('پیامک');
    }
}
