<?php

/**
 * Khaneh Romantic — Contacts and the forms inbox (CRM base)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Other modules define their forms (Forms::define) and record what visitors send (Forms::record);
 * customer accounts and orders keep the contacts list complete. Campaign parameters (utm_*) are
 * remembered for the session so every submission shows where the visitor came from.
 */

namespace Modules\Crm\Providers;

use App\Core\Modules\ModuleServiceProvider;
use App\Core\Settings\Settings;
use App\Core\Settings\SettingsSection;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Modules\Crm\Http\Middleware\CaptureCampaign;
use Modules\Crm\Support\Forms;
use Modules\Customer\Models\Customer;

class CrmServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        $this->app->make(Router::class)->pushMiddlewareToGroup('web', CaptureCampaign::class);

        // every customer account and every order buyer is a contact too
        Event::listen('eloquent.created: '.Customer::class, fn (Customer $customer) => rescue(fn () => Forms::contact([
            'mobile' => $customer->mobile, 'name' => $customer->fullName(), 'email' => $customer->email, 'customer_id' => $customer->id,
        ]), report: true));
        Event::listen('order.placed', fn (array $order) => rescue(fn () => Forms::contact([
            'mobile' => $order['mobile'], 'name' => $order['name'], 'customer_id' => $order['customer_id'],
        ]), report: true));

        Settings::section(new SettingsSection(
            key: 'crm',
            title: 'فرم‌ها و مخاطبان',
            icon: 'heroicon-o-inbox-stack',
            sort: 45,
            defaults: ['alert_mobiles' => null, 'alert_forms' => ['contact']],
            schema: fn () => [
                TextInput::make('alert_mobiles')->label('پیامک فرم‌های تازه به این شماره‌ها')
                    ->placeholder('09121234567, 09351234567')->extraInputAttributes(['dir' => 'ltr'])
                    ->helperText('خالی = بدون پیامک. متن پیامک: نام فرم، فرستنده و خلاصه‌ی پیام.'),
                CheckboxList::make('alert_forms')->label('برای کدام فرم‌ها')
                    ->options(fn () => collect(Forms::all())->filter(fn ($f) => $f->alert)->map(fn ($f) => $f->label)->all())
                    ->columns(2),
            ],
        ));
    }
}
