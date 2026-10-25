<?php

/**
 * About / Contact page editor: starter blocks, editing blocks in the admin, FAQ schema, reset.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Content\Tests\Feature;

use App\Models\User;
use Filament\Forms\Components\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Content\Filament\Resources\Pages\Pages\EditPage;
use Modules\Content\Filament\Resources\Pages\Pages\ListPages;
use Modules\Content\Models\Page;
use Tests\TestCase;

class PagesEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_start_with_the_old_content_and_are_edited_with_blocks(): void
    {
        $this->get('/aboutus')->assertOk()->assertSee('بیش از ۲۰ سال همراه خانه‌های شیک و گرم')->assertSee('داستان ما')->assertSee('کالای اصل با ضمانت اصالت');
        $this->get('/privacy-policy')->assertOk()->assertSee('حریم خصوصی شما')->assertSee('چه اطلاعاتی نگه می‌داریم');
        $this->get('/return-policy')->assertOk()->assertSee('شرایط مرجوعی و بازگشت کالا')->assertSee('۷ روز')->assertSee('مراحل مرجوعی')
            ->assertSee(route('pages.privacy'))->assertSee(route('pages.returns')); // footer links
        $this->get('/contact-us')->assertOk()->assertSee(config('khaneh.store.email'))->assertSee(config('khaneh.store.address'))->assertSee('پیام بگذارید');

        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(ListPages::class)->assertCanSeeTableRecords(Page::all())->assertSee('تماس با ما');

        $about = Page::forKey('about');
        $undo = Builder::fake();
        Livewire::test(EditPage::class, ['record' => $about->id])
            ->fillForm([
                'title' => 'داستان ما',
                'meta_description' => 'از سال ۱۳۹۰ همراه خانه‌های ایرانی',
                'blocks' => [
                    ['type' => 'hero', 'data' => ['eyebrow' => 'Since 2011', 'title' => 'داستان خانه رمانتیک', 'text' => 'از یک مغازه‌ی کوچک در بازار تهران…', 'image' => null]],
                    ['type' => 'timeline', 'data' => ['title' => 'مسیر ما', 'items' => [['year' => '۱۳۹۰', 'title' => 'شروع', 'text' => 'اولین فروشگاه']]]],
                    ['type' => 'faq', 'data' => ['title' => 'پرسش‌ها', 'items' => [['question' => 'کالاها اصل است؟', 'answer' => 'بله، با ضمانت اصالت.']]]],
                    ['type' => 'cta', 'data' => ['title' => 'سری به فروشگاه بزنید', 'text' => null, 'button_label' => 'فروشگاه', 'button_url' => '/shop']],
                ],
            ])
            ->call('save')->assertHasNoFormErrors();
        $undo();

        $html = $this->get('/aboutus')->assertOk()
            ->assertSee('داستان خانه رمانتیک')->assertSee('مسیر ما')->assertSee('اولین فروشگاه')->assertSee('سری به فروشگاه بزنید')
            ->assertDontSee('کالای اصل با ضمانت اصالت')
            ->assertSee('<meta name="description" content="از سال ۱۳۹۰ همراه خانه‌های ایرانی">', false)
            ->getContent();
        $this->assertStringContainsString('"@type":"FAQPage"', $html);
        $this->assertStringContainsString('کالاها اصل است؟', $html);

        Livewire::test(EditPage::class, ['record' => $about->id])->callAction('reset');
        $this->get('/aboutus')->assertSee('کالای اصل با ضمانت اصالت');
    }
}
