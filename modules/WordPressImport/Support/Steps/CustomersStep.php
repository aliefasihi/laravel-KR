<?php

/**
 * Khaneh Romantic — WordPress users (customers) → customers by mobile, with their addresses
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * The mobile is the account (OTP login), so it comes from Digits (digits_phone), the billing
 * phone or a mobile-looking username. Users without any valid mobile are skipped and listed.
 * Guest buyers become customers in the orders step (from the order's phone).
 */

namespace Modules\WordPressImport\Support\Steps;

use App\Core\Support\PersianText;
use Modules\Customer\Models\Customer;
use Modules\Customer\Support\Mobile;
use Modules\WordPressImport\Support\IranStates;
use Modules\WordPressImport\Support\Report;

class CustomersStep extends Step
{
    public const META = ['digits_phone', 'digits_phone_no', 'digt_countrycode', 'billing_phone', 'shipping_phone', 'first_name', 'last_name',
        'billing_first_name', 'billing_last_name', 'billing_address_1', 'billing_address_2', 'billing_city', 'billing_state', 'billing_postcode',
        'shipping_first_name', 'shipping_last_name', 'shipping_address_1', 'shipping_address_2', 'shipping_city', 'shipping_state', 'shipping_postcode'];

    public function key(): string
    {
        return 'customers';
    }

    public function label(): string
    {
        return 'مشتریان (کاربران وردپرس)';
    }

    public function requires(): array
    {
        return ['Customer'];
    }

    public function sourceCount(): int
    {
        return $this->wp->table('users')->count();
    }

    public function importedCount(): int
    {
        return Customer::query()->whereNotNull('legacy_wp_id')->count();
    }

    public function run(Report $report): void
    {
        $prefix = config('modules.wordpressimport.connection.prefix');
        $query = $this->wp->table('users')->orderBy('ID');
        if ($report->limit) {
            $query->limit($report->limit);
        }

        $query->chunk(200, function ($users) use ($report, $prefix) {
            $ids = $users->pluck('ID')->map(fn ($id) => (int) $id)->all();
            $meta = $this->wp->userMeta($ids, [...self::META, $prefix.'capabilities']);

            foreach ($users as $user) {
                $m = $meta[(int) $user->ID] ?? [];
                $roles = array_keys(array_filter((array) ($m[$prefix.'capabilities'] ?? [])));
                if (array_intersect($roles, ['administrator', 'shop_manager', 'editor', 'author'])) {
                    $report->count('customers', 'skipped');

                    continue; // staff stay in WordPress / become admin users by hand
                }

                $mobile = self::mobileFrom($m, (string) $user->user_login);
                if (! $mobile) {
                    $report->count('customers', 'skipped');
                    $report->warn('customers', "کاربر {$user->ID} ({$user->user_login}) شماره‌ی موبایل معتبر ندارد؛ رد شد.");

                    continue;
                }

                $existing = Customer::query()->where('legacy_wp_id', 'u'.$user->ID)->first() ?? Customer::query()->where('mobile', $mobile)->first();
                $report->count('customers', $existing ? 'updated' : 'created');
                if ($report->dryRun) {
                    continue;
                }

                $customer = $existing ?? new Customer(['mobile' => $mobile]);
                $customer->fill([
                    'first_name' => $customer->first_name ?: (($m['first_name'] ?? '') ?: ($m['billing_first_name'] ?? null)) ?: null,
                    'last_name' => $customer->last_name ?: (($m['last_name'] ?? '') ?: ($m['billing_last_name'] ?? null)) ?: null,
                    'email' => $customer->email ?: (self::realEmail($user->user_email) ?: null),
                    'legacy_wp_id' => 'u'.$user->ID,
                ]);
                $customer->mobile_verified_at ??= $this->date($user->user_registered);
                if (! $customer->exists) {
                    $customer->created_at = $this->date($user->user_registered) ?? now();
                }
                $customer->save();

                self::address($customer, $m, 'billing');
                self::address($customer, $m, 'shipping');
            }
        });
    }

    /** Digits (+98…), billing phone, or a mobile-looking username. */
    public static function mobileFrom(array $meta, string $login = ''): ?string
    {
        foreach ([
            ($meta['digt_countrycode'] ?? '').($meta['digits_phone_no'] ?? ''),
            $meta['digits_phone'] ?? null,
            $meta['digits_phone_no'] ?? null,
            $meta['billing_phone'] ?? null,
            $meta['shipping_phone'] ?? null,
            $login,
        ] as $candidate) {
            if ($mobile = Mobile::normalize((string) $candidate)) {
                return $mobile;
            }
        }

        return null;
    }

    /** WooCommerce fills fake emails for phone-only accounts (Digits) — those aren't kept. */
    public static function realEmail(?string $email): ?string
    {
        $email = trim((string) $email);

        return $email && filter_var($email, FILTER_VALIDATE_EMAIL) && ! preg_match('/^(\+?\d+|09\d+)@|@(example|localhost|digits)/i', $email) ? $email : null;
    }

    public static function address(Customer $customer, array $m, string $type, string $prefix = ''): void
    {
        $line = trim(($m[$prefix.$type.'_address_1'] ?? '').' '.($m[$prefix.$type.'_address_2'] ?? ''));
        if ($line === '' || ($m[$prefix.$type.'_city'] ?? '') === '') {
            return;
        }
        if ($customer->addresses()->where('address', $line)->exists()) {
            return;
        }

        $customer->addresses()->create([
            'title' => $type === 'shipping' ? 'نشانی ارسال' : null,
            'recipient_name' => trim(($m[$prefix.$type.'_first_name'] ?? '').' '.($m[$prefix.$type.'_last_name'] ?? '')) ?: ($customer->fullName() ?: $customer->mobile),
            'recipient_mobile' => Mobile::normalize($m[$prefix.$type.'_phone'] ?? null) ?? $customer->mobile,
            'province' => IranStates::name($m[$prefix.$type.'_state'] ?? null),
            'city' => $m[$prefix.$type.'_city'],
            'address' => $line,
            'postal_code' => preg_match('/^\d{10}$/', $p = preg_replace('/\D/', '', PersianText::toLatinDigits((string) ($m[$prefix.$type.'_postcode'] ?? '')))) ? $p : null,
        ]);
    }
}
