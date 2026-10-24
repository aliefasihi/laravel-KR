<?php

/**
 * «خروجی» on admin lists: every list has it, it exports what the search/filters select, in Excel or CSV,
 * with the table's own texts; amounts stay numbers, mobiles stay text.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Tests\Feature;

use App\Filament\Support\TableExport;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Crm\Filament\Resources\Contacts\Pages\ListContacts;
use Modules\Crm\Models\Contact;
use Modules\Customer\Filament\Resources\Customers\Pages\ListCustomers;
use Modules\Customer\Models\Customer;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;

class TableExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    public function test_every_list_page_has_the_export_button(): void
    {
        $checked = 0;
        foreach (Filament::getPanel('admin')->getResources() as $resource) {
            $index = $resource::getPages()['index'] ?? null;
            if ($index === null || ! is_subclass_of($page = $index->getPage(), ListRecords::class)) {
                continue;
            }
            Livewire::test($page)->assertActionExists(TestAction::make('exportXlsx')->table());
            $checked++;
        }
        $this->assertGreaterThan(30, $checked);
    }

    public function test_rows_follow_the_search_and_keep_numbers_and_mobiles(): void
    {
        Customer::create(['mobile' => '09120000001', 'first_name' => 'آرش', 'last_name' => 'کریمی']);
        Customer::create(['mobile' => '09120000002', 'first_name' => 'سارا']);

        $page = Livewire::test(ListCustomers::class)->set('tableSearch', 'آرش')->instance();
        [$headers, $rows] = TableExport::rows($page);

        $this->assertContains('موبایل', $headers);
        $this->assertCount(1, $rows);
        $mobile = $rows[0][array_search('موبایل', $headers, true)];
        $this->assertSame('09120000001', $mobile);   // leading zero kept: text, not a number

        $this->assertSame(1200000, (new \ReflectionMethod(TableExport::class, 'number'))->invoke(null, '۱٬۲۰۰٬۰۰۰ تومان'));
        $this->assertNull((new \ReflectionMethod(TableExport::class, 'number'))->invoke(null, '۱۴۰۵/۰۸/۰۳'));
    }

    public function test_excel_and_csv_downloads(): void
    {
        Contact::create(['mobile' => '09121111111', 'name' => 'نگار', 'source' => 'manual', 'first_seen_at' => now(), 'last_seen_at' => now()]);

        $xlsx = Livewire::test(ListContacts::class)->callAction(TestAction::make('exportXlsx')->table());
        $xlsx->assertFileDownloaded();
        $file = $xlsx->effects['download'] ?? null;
        $this->assertNotNull($file);
        $this->assertStringEndsWith('.xlsx', $file['name']);

        $path = tempnam(sys_get_temp_dir(), 'kr').'.xlsx';
        file_put_contents($path, base64_decode($file['content']));
        $reader = new Reader;
        $reader->open($path);
        $cells = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells[] = $row->toArray();
            }
        }
        $reader->close();
        $this->assertCount(2, $cells);   // header + one contact
        $this->assertContains('نگار', $cells[1]);
        $this->assertContains('09121111111', $cells[1]);

        $csv = Livewire::test(ListContacts::class)->callAction(TestAction::make('exportCsv')->table());
        $csv->assertFileDownloaded();
        $content = base64_decode($csv->effects['download']['content']);
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('نگار', $content);
    }
}
