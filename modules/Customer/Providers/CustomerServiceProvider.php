<?php

/**
 * Khaneh Romantic — Customer module (accounts, OTP login, addresses)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Shoppers log in with mobile + SMS code on their own guard («customer»), separate from admin
 * users. Adds the login/account button to the header and drawer, and the account area.
 */

namespace Modules\Customer\Providers;

use App\Core\Modules\ModuleServiceProvider;
use App\Core\Support\StorefrontHooks;
use Illuminate\Console\Scheduling\Schedule;
use Livewire\Livewire;
use Modules\Customer\Livewire\Addresses;
use Modules\Customer\Livewire\Login;
use Modules\Customer\Livewire\Profile;
use Modules\Customer\Models\Customer;
use Modules\Customer\Support\Otp;

class CustomerServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->app->scoped(Otp::class);

        config([
            'auth.guards.customer' => ['driver' => 'session', 'provider' => 'customers'],
            'auth.providers.customers' => ['driver' => 'eloquent', 'model' => Customer::class],
        ]);
    }

    public function boot(): void
    {
        parent::boot();

        Livewire::component('customer.login', Login::class);
        Livewire::component('customer.profile', Profile::class);
        Livewire::component('customer.addresses', Addresses::class);

        StorefrontHooks::add('header.actions', 'customer::partials.account-button', 20);
        StorefrontHooks::add('drawer.footer', 'customer::partials.drawer-link', 5);

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->call(fn () => app(Otp::class)->prune())->daily()->name('customer:prune-otps');
        });
    }
}
