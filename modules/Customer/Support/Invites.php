<?php

/**
 * Khaneh Romantic — «کد معرف» field on the sign-up form, filled in by whichever module runs invites
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * The Customer module only shows the field; the club (Loyalty) registers how a code is checked,
 * what is prefilled (the ?ref= link cookie) and what happens when an existing account enters one.
 * While a new account is being created, the entered code is in $pending so the club's
 * «customer created» listener can use it instead of the link cookie.
 */

namespace Modules\Customer\Support;

use Closure;
use Modules\Customer\Models\Customer;

final class Invites
{
    public ?string $pending = null;

    private ?Closure $check = null;

    private ?Closure $prefill = null;

    private ?Closure $apply = null;

    private ?Closure $enabled = null;

    /**
     * @param  Closure(): bool  $enabled  show the field at all
     * @param  Closure(string): ?string  $check  code → normalized code, or null if unknown
     * @param  Closure(): string  $prefill  code to show already filled in (e.g. from an invite link)
     * @param  Closure(Customer, string): bool  $apply  an existing account entered a code
     */
    public function using(Closure $enabled, Closure $check, Closure $prefill, Closure $apply): void
    {
        [$this->enabled, $this->check, $this->prefill, $this->apply] = [$enabled, $check, $prefill, $apply];
    }

    public function enabled(): bool
    {
        return $this->enabled !== null && (bool) ($this->enabled)();
    }

    public function check(string $code): ?string
    {
        return $this->check ? ($this->check)($code) ?: null : null;
    }

    public function prefill(): string
    {
        return $this->prefill ? (string) ($this->prefill)() : '';
    }

    /** Run the sign-up/sign-in with the entered code visible to «customer created» listeners. */
    public function forSignup(string $code, Closure $createOrFind): Customer
    {
        $this->pending = $code;
        try {
            return $createOrFind();
        } finally {
            $this->pending = null;
        }
    }

    public function apply(Customer $customer, string $code): bool
    {
        return $this->apply ? (bool) ($this->apply)($customer, $code) : false;
    }
}
