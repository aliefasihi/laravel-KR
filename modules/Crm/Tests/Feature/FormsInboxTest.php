<?php

/**
 * Forms inbox and contacts: contact form, newsletter, campaign capture, SMS alert, admin tabs,
 * opening/marking, exports, contact history.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Crm\Tests\Feature;

use App\Core\Settings\Settings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Content\Livewire\ContactForm;
use Modules\Content\Livewire\NewsletterForm;
use Modules\Content\Models\NewsletterSubscriber;
use Modules\Content\Support\Newsletter;
use Modules\Crm\Filament\Resources\Contacts\Pages\ListContacts;
use Modules\Crm\Filament\Resources\Contacts\Pages\ViewContact;
use Modules\Crm\Filament\Resources\Contacts\RelationManagers\SubmissionsRelationManager;
use Modules\Crm\Filament\Resources\FormSubmissions\Pages\ListFormSubmissions;
use Modules\Crm\Models\Contact;
use Modules\Crm\Models\FormSubmission;
use Modules\Crm\Support\Campaign;
use Modules\Crm\Support\Forms;
use Modules\Customer\Models\Customer;
use Modules\Sms\Support\Sms;
use Modules\Sms\Support\SmsDriver;
use Tests\TestCase;

class FormsInboxTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<array{0: string, 1: string}> */
    public array $texts = [];

    protected function setUp(): void
    {
        parent::setUp();
        $texts = &$this->texts;
        app(Sms::class)->use(new class($texts) implements SmsDriver
        {
            public function __construct(private array &$texts) {}

            public function sendOtp(string $mobile, string $code): void {}

            public function sendTemplate(string $mobile, string $template, array $values): void {}

            public function sendText(string $mobile, string $text): void
            {
                $this->texts[] = [$mobile, $text];
            }
        });
    }

    private function contactForm()
    {
        $form = Livewire::test(ContactForm::class);
        $this->travel(5)->seconds();   // people take a few seconds; bots don't

        return $form;
    }

    public function test_contact_form_lands_in_the_inbox_with_its_contact_campaign_and_sms_alert(): void
    {
        Settings::set(['crm.alert_mobiles' => '09120000000', 'crm.alert_forms' => ['contact']]);
        $this->get('/contact-us?utm_source=instagram&utm_campaign=yalda')->assertOk()
            ->assertSee('پیام بگذارید')->assertSee(config('khaneh.store.email'));

        $form = $this->contactForm()
            ->call('send')->assertHasErrors(['name', 'mobile', 'message'])
            ->set('name', 'علی فصیحی')->set('mobile', '۰۹۱۲ ۱۱۱ ۲۲۳۳')->set('email', 'ali@example.com')
            ->set('subject', 'پیگیری سفارش')->set('orderNumber', '۱۰۰۰۱۲')->set('message', 'سفارشم کی ارسال می‌شود؟');
        $form->call('send')->assertHasNoErrors();

        $submission = FormSubmission::query()->sole();
        $form->assertSet('sentId', $submission->id)->assertSee('پیام شما رسید');
        $this->assertSame('contact', $submission->form);
        $this->assertSame('new', $submission->status);
        $this->assertSame(['name' => 'علی فصیحی', 'mobile' => '09121112233', 'email' => 'ali@example.com', 'subject' => 'پیگیری سفارش', 'order_number' => '100012', 'message' => 'سفارشم کی ارسال می‌شود؟'], $submission->data);

        $contact = Contact::query()->sole();
        $this->assertSame('09121112233', $contact->mobile);
        $this->assertSame('ali@example.com', $contact->email);
        $this->assertSame('contact', $contact->source);
        $this->assertSame(1, $contact->submissions_count);

        $this->assertCount(1, $this->texts);
        $this->assertSame('09120000000', $this->texts[0][0]);
        $this->assertStringContainsString('تماس با ما', $this->texts[0][1]);

        // bots: honeypot, or too fast
        $this->contactForm()->set('website', 'http://spam')->set('name', 'x')->set('mobile', '09121112233')->set('message', 'buy cheap stuff now')->call('send')->assertSet('sentId', 0);
        Livewire::test(ContactForm::class)->set('name', 'x')->set('mobile', '09121112233')->set('message', 'buy cheap stuff now')->call('send')->assertSet('sentId', 0);
        $this->assertSame(1, FormSubmission::query()->count());

        // the campaign of the visit (utm_*) is kept in the session and stored with the next form
        $this->assertSame(['utm_source' => 'instagram', 'utm_campaign' => 'yalda', 'landing' => 'contact-us'], session(Campaign::SESSION_KEY));
        request()->setLaravelSession(session()->driver());
        $tagged = Forms::record('contact', ['message' => 'سلام'], ['mobile' => '09129998877']);
        $this->assertSame('yalda', $tagged->utm['utm_campaign']);
        $this->assertSame('instagram', $tagged->contact->utm['utm_source']);
    }

    public function test_newsletter_inline_form_customer_flag_and_unsubscribe_link(): void
    {
        $customer = Customer::create(['mobile' => '09123334455', 'first_name' => 'سارا']);
        $this->assertDatabaseHas('contacts', ['mobile' => '09123334455', 'customer_id' => $customer->id]);   // accounts are contacts

        Livewire::test(NewsletterForm::class)->set('contact', 'hello')->call('subscribe')->assertHasErrors('contact')
            ->set('contact', '+98 912 333 4455')->call('subscribe')->assertSee('عضویت شما ثبت شد');
        Livewire::test(NewsletterForm::class)->set('contact', '09123334455')->call('subscribe')->assertSee('از قبل عضو است');
        Livewire::test(NewsletterForm::class)->set('contact', 'Sara@Example.com')->call('subscribe');

        $this->assertSame(2, NewsletterSubscriber::query()->count());
        $this->assertTrue($customer->fresh()->newsletter);
        $this->assertTrue(Contact::query()->where('mobile', '09123334455')->value('newsletter_sms'));
        $this->assertSame(2, FormSubmission::query()->where('form', 'newsletter')->where('status', 'read')->count());

        // unsubscribe: signed link only
        $subscriber = NewsletterSubscriber::query()->where('contact', '09123334455')->sole();
        $this->get('/newsletter/unsubscribe/'.$subscriber->id)->assertForbidden();
        $this->get(Newsletter::unsubscribeUrl($subscriber))->assertOk()->assertSee('عضویت شما لغو شد');
        $this->assertNotNull($subscriber->fresh()->unsubscribed_at);
        $this->assertFalse($customer->fresh()->newsletter);
        $this->assertFalse(Contact::query()->where('mobile', '09123334455')->value('newsletter_sms'));

        // the profile checkbox signs up again
        $customer->update(['newsletter' => true]);
        $this->assertNull($subscriber->fresh()->unsubscribed_at);

        // the old no-JS form still works
        $this->from('/')->post('/newsletter', ['contact' => '09125556677'])->assertRedirect('/')->assertSessionHas('status');
        $this->assertSame(3, NewsletterSubscriber::query()->active()->count());
    }

    public function test_admin_inbox_tabs_open_export_and_contact_history(): void
    {
        foreach ([['رضا', '09121110000', 'قیمت عمده؟'], ['نگار', '09351110000', 'ساعت کاری؟']] as [$name, $mobile, $message]) {
            $this->contactForm()->set('name', $name)->set('mobile', $mobile)->set('message', $message.' لطفاً پاسخ دهید')->call('send');
        }
        Newsletter::subscribe('09121110000');
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->get('/admin/inbox')->assertOk()->assertSee('تماس با ما')->assertSee('خبرنامه')->assertSee('قیمت عمده؟');
        $page = Livewire::test(ListFormSubmissions::class)
            ->assertCanSeeTableRecords(FormSubmission::all())
            ->set('activeTab', 'contact')
            ->assertCanSeeTableRecords(FormSubmission::query()->where('form', 'contact')->get())
            ->assertCanNotSeeTableRecords(FormSubmission::query()->where('form', 'newsletter')->get())
            ->assertSee('ساعت کاری؟ لطفاً پاسخ دهید');

        $first = FormSubmission::query()->where('form', 'contact')->first();
        $page->callTableAction('open', $first, ['status' => 'done', 'note' => 'تماس گرفته شد']);
        $first->refresh();
        $this->assertSame('done', $first->status);
        $this->assertSame('تماس گرفته شد', $first->note);
        $this->assertNotNull($first->handled_at);

        $page->callAction('export')->assertFileDownloaded();
        $content = base64_decode((string) data_get($page->effects, 'download.content'));
        $this->assertStringContainsString('پیام', $content);
        $this->assertStringContainsString('قیمت عمده؟', $content);
        $this->assertStringNotContainsString('خبرنامه', $content);

        // contacts: one per mobile, history on the contact page, tags
        $reza = Contact::query()->where('mobile', '09121110000')->sole();
        $this->assertSame(2, $reza->submissions_count);
        $this->get('/admin/contacts')->assertOk()->assertSee('رضا');
        $this->get('/admin/contacts/'.$reza->id)->assertOk()->assertSee('رضا');
        Livewire::test(SubmissionsRelationManager::class, ['ownerRecord' => $reza, 'pageClass' => ViewContact::class])
            ->assertSee('قیمت عمده؟')->assertSee('خبرنامه');
        Livewire::test(ListContacts::class)->callTableBulkAction('tag', [$reza], ['tags' => ['عمده‌فروش']]);
        $this->assertSame(['عمده‌فروش'], $reza->fresh()->tags);
        Livewire::test(ListContacts::class)->filterTable('tag', 'عمده‌فروش')->assertCanSeeTableRecords([$reza])
            ->assertCanNotSeeTableRecords(Contact::query()->where('mobile', '09351110000')->get());
        Livewire::test(ViewContact::class, ['record' => $reza->id])->assertOk();
    }
}
