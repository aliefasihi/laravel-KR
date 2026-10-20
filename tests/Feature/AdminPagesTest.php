<?php

/**
 * Every admin list page opens (with a record in it), and table filters really filter.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Attributes\Models\AttributeGroup;
use Modules\Customer\Filament\Resources\Customers\Pages\ListCustomers;
use Modules\Customer\Models\Customer;
use Modules\Search\Filament\Resources\SearchTerms\Pages\ManageSearchTerms;
use Modules\Search\Models\SearchTerm;
use Tests\TestCase;

class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    public function test_every_resource_list_page_opens(): void
    {
        AttributeGroup::query()->create(['name' => 'ظروف']);   // a row in the table: columns get formatted

        $resources = Filament::getPanel('admin')->getResources();
        $this->assertNotEmpty($resources);
        foreach ($resources as $resource) {
            if (! $resource::hasPage('index')) {
                continue;
            }
            $this->get($resource::getUrl('index'))->assertOk();
        }
    }

    public function test_table_filters_filter(): void
    {
        $named = Customer::create(['mobile' => '09120000001', 'first_name' => 'آرش']);
        $nameless = Customer::create(['mobile' => '09120000002', 'newsletter' => true]);
        Livewire::test(ListCustomers::class)->filterTable('no_name')->assertCanSeeTableRecords([$nameless])->assertCanNotSeeTableRecords([$named]);
        Livewire::test(ListCustomers::class)->filterTable('newsletter')->assertCanSeeTableRecords([$nameless])->assertCanNotSeeTableRecords([$named]);

        $found = SearchTerm::query()->create(['term' => 'بشقاب', 'searches' => 3, 'last_results' => 12, 'last_searched_at' => now()->subDays(40)]);
        $missing = SearchTerm::query()->create(['term' => 'قوری مسی', 'searches' => 1, 'last_results' => 0, 'last_searched_at' => now()]);
        Livewire::test(ManageSearchTerms::class)->assertCanSeeTableRecords([$missing])->assertCanNotSeeTableRecords([$found]);   // «فقط بی‌نتیجه‌ها» is on by default
        Livewire::test(ManageSearchTerms::class)->removeTableFilters()->filterTable('recent')->assertCanSeeTableRecords([$missing])->assertCanNotSeeTableRecords([$found]);
    }
}
