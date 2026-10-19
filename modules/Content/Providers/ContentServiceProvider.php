<?php

/**
 * Khaneh Romantic — Content module (blog, editable pages, newsletter, contact form)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Content\Providers;

use App\Core\Modules\ModuleServiceProvider;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Modules\Content\Filament\Resources\NewsletterSubscribers\NewsletterSubscriberResource;
use Modules\Content\Livewire\ContactForm;
use Modules\Content\Livewire\NewsletterForm;
use Modules\Content\Models\NewsletterSubscriber;
use Modules\Content\Support\Newsletter;
use Modules\Crm\Support\FormDefinition;
use Modules\Crm\Support\Forms;
use Modules\Customer\Models\Customer;

class ContentServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        Livewire::component('content.newsletter', NewsletterForm::class);
        Livewire::component('content.contact-form', ContactForm::class);

        Forms::define(new FormDefinition('contact', 'تماس با ما', [
            'name' => 'نام', 'mobile' => 'موبایل', 'email' => 'ایمیل', 'subject' => 'موضوع', 'order_number' => 'شماره سفارش', 'message' => 'پیام',
        ], icon: 'heroicon-o-envelope', sort: 10));
        Forms::define(new FormDefinition('newsletter', 'خبرنامه', [
            'contact' => 'شماره / ایمیل', 'channel' => 'نوع', 'source' => 'از کجا',
        ], icon: 'heroicon-o-newspaper', sort: 20,
            subjectUrl: fn () => NewsletterSubscriberResource::getUrl()));

        // «عضویت در خبرنامه» in the customer's profile ⇄ the newsletter list
        Event::listen('eloquent.saved: '.Customer::class, function (Customer $customer) {
            if (! $customer->wasChanged('newsletter') && ! ($customer->wasRecentlyCreated && $customer->newsletter)) {
                return;
            }
            if ($customer->newsletter) {
                Newsletter::subscribe($customer->mobile, 'profile');
            } elseif ($subscriber = NewsletterSubscriber::query()->where('contact', $customer->mobile)->whereNull('unsubscribed_at')->first()) {
                Newsletter::unsubscribe($subscriber);
            }
        });
    }
}
