<?php

/**
 * Khaneh Romantic — The forms registry and the single way to record a submission
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 *   Forms::define(new FormDefinition('contact', 'تماس با ما', ['name' => 'نام', 'message' => 'پیام']));
 *   Forms::record('contact', $data, ['name' => …, 'mobile' => …, 'email' => …], subject: $model);
 *
 * Recording finds or creates the contact (by mobile, then email), links the customer account
 * with the same mobile, stores page/IP/campaign and alerts the staff by SMS when asked to.
 * It never throws: a failing inbox must not break the form that called it.
 */

namespace Modules\Crm\Support;

use App\Core\Modules\Modules;
use App\Core\Settings\Settings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Crm\Models\Contact;
use Modules\Crm\Models\FormSubmission;
use Modules\Customer\Models\Customer;
use Modules\Customer\Support\Mobile;
use Modules\Sms\Support\Sms;
use Throwable;

final class Forms
{
    /** @var array<string, FormDefinition> */
    private static array $forms = [];

    public static function define(FormDefinition $form): void
    {
        self::$forms[$form->key] = $form;
    }

    /** @return array<string, FormDefinition> sorted for the tabs */
    public static function all(): array
    {
        return collect(self::$forms)->sortBy('sort')->all();
    }

    public static function get(string $key): ?FormDefinition
    {
        return self::$forms[$key] ?? null;
    }

    public static function flush(): void
    {
        self::$forms = [];
    }

    /**
     * @param  array<string, mixed>  $data  what the visitor filled in
     * @param  array{name?: ?string, mobile?: ?string, email?: ?string, customer_id?: ?int, newsletter_sms?: bool, newsletter_email?: bool}  $person
     */
    public static function record(string $form, array $data, array $person = [], ?Model $subject = null, string $status = 'new'): ?FormSubmission
    {
        if (! Modules::enabled('Crm')) {
            return null;
        }

        try {
            $contact = self::contact($person, $form);
            $request = request();
            $submission = FormSubmission::create([
                'form' => $form,
                'contact_id' => $contact?->id,
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'data' => array_map(fn ($v) => is_string($v) ? trim($v) : $v, $data),
                'status' => $status,
                'page_url' => Str::limit((string) ($request->headers->get('referer') ?: $request->fullUrl()), 490, ''),
                'ip' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
                'utm' => Campaign::current() ?: null,
            ]);
            if ($status !== 'spam') {
                self::alert($submission);
            }

            return $submission;
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /** Find or create the contact for a person; null when there is nothing to identify them by. */
    public static function contact(array $person, ?string $source = null): ?Contact
    {
        $mobile = Mobile::normalize($person['mobile'] ?? null);
        $email = filter_var(trim((string) ($person['email'] ?? '')), FILTER_VALIDATE_EMAIL) ? mb_strtolower(trim($person['email'])) : null;
        $customerId = $person['customer_id'] ?? null;
        if (! $mobile && $customerId) {
            $mobile = Customer::query()->whereKey($customerId)->value('mobile');
        }
        if (! $mobile && ! $email) {
            return null;
        }

        $contact = ($mobile ? Contact::query()->where('mobile', $mobile)->first() : null)
            ?? ($email ? Contact::query()->where('email', $email)->first() : null)
            ?? new Contact(['source' => $source, 'first_seen_at' => now(), 'utm' => Campaign::current() ?: null]);

        $name = trim((string) ($person['name'] ?? ''));
        $contact->fill(array_filter([
            'mobile' => $contact->mobile ?: $mobile,
            'email' => $contact->email ?: ($email && ! Contact::query()->where('email', $email)->whereKeyNot($contact->id ?? 0)->exists() ? $email : null),
            'name' => $contact->name ?: ($name !== '' ? Str::limit($name, 120, '') : null),
            'customer_id' => $contact->customer_id ?: ($customerId ?: ($mobile ? Customer::query()->where('mobile', $mobile)->value('id') : null)),
        ], fn ($v) => $v !== null && $v !== ''));
        foreach (['newsletter_sms', 'newsletter_email'] as $flag) {
            if (! empty($person[$flag])) {
                $contact->{$flag} = true;
                $contact->unsubscribed_at = null;
            }
        }
        $contact->last_seen_at = now();
        if ($source !== null) {
            $contact->submissions_count = (int) $contact->submissions_count + 1;
        }
        $contact->save();

        return $contact;
    }

    /** SMS to the staff numbers for the forms they chose (settings → فرم‌ها و مخاطبان). */
    private static function alert(FormSubmission $submission): void
    {
        $forms = (array) Settings::get('crm.alert_forms', []);
        $mobiles = array_filter(array_map(fn ($m) => Mobile::normalize($m), preg_split('/[\s,،]+/', (string) Settings::get('crm.alert_mobiles'))));
        if (! $mobiles || ! in_array($submission->form, $forms, true) || ! Modules::enabled('Sms')) {
            return;
        }
        $label = self::get($submission->form)?->label ?? $submission->form;
        $who = $submission->contact?->displayName() ?? 'بازدیدکننده';
        $text = "فرم «{$label}» از {$who}\n".Str::limit(collect($submission->data)->filter(fn ($v) => is_scalar($v) && $v !== '')->implode(' | '), 120);
        foreach ($mobiles as $mobile) {
            rescue(fn () => app(Sms::class)->text($mobile, $text), report: true);
        }
    }
}
