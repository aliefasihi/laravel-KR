<?php

/**
 * Khaneh Romantic — Newsletter sign-up / sign-out (footer, customer profile, admin)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Accepts an email or a mobile in any form (۰۹۱۲…, +98…), keeps one row per address, and keeps
 * the customer's «خبرنامه» checkbox and the contact in the forms inbox in step.
 */

namespace Modules\Content\Support;

use App\Core\Modules\Modules;
use App\Core\Support\PersianText;
use Illuminate\Support\Facades\URL;
use Modules\Content\Models\NewsletterSubscriber;
use Modules\Crm\Models\Contact;
use Modules\Crm\Support\Forms;
use Modules\Customer\Models\Customer;
use Modules\Customer\Support\Mobile;

final class Newsletter
{
    /** @return array{contact: string, channel: string}|null */
    public static function parse(?string $raw): ?array
    {
        $raw = trim(PersianText::toLatinDigits((string) $raw));
        if ($mobile = Mobile::normalize($raw)) {
            return ['contact' => $mobile, 'channel' => 'sms'];
        }

        return filter_var($raw, FILTER_VALIDATE_EMAIL) ? ['contact' => mb_strtolower($raw), 'channel' => 'email'] : null;
    }

    /**
     * @return array{status: 'created'|'exists'|'invalid', subscriber?: NewsletterSubscriber}
     */
    public static function subscribe(?string $raw, string $source = 'footer'): array
    {
        $parsed = self::parse($raw);
        if (! $parsed) {
            return ['status' => 'invalid'];
        }

        $subscriber = NewsletterSubscriber::query()->firstOrNew(['contact' => $parsed['contact']]);
        $existed = $subscriber->exists && $subscriber->unsubscribed_at === null;
        $subscriber->fill(['channel' => $parsed['channel'], 'source' => $subscriber->source ?: $source, 'unsubscribed_at' => null])->save();

        if (! $existed) {
            if ($parsed['channel'] === 'sms') {
                Customer::query()->where('mobile', $parsed['contact'])->where('newsletter', false)->update(['newsletter' => true]);
            }
            Forms::record('newsletter', ['contact' => $parsed['contact'], 'channel' => NewsletterSubscriber::CHANNELS[$parsed['channel']], 'source' => NewsletterSubscriber::SOURCES[$source] ?? $source], [
                $parsed['channel'] === 'sms' ? 'mobile' : 'email' => $parsed['contact'],
                'newsletter_'.$parsed['channel'] => true,
            ], $subscriber, status: 'read');
        }

        return ['status' => $existed ? 'exists' : 'created', 'subscriber' => $subscriber];
    }

    public static function unsubscribe(NewsletterSubscriber $subscriber): void
    {
        $subscriber->forceFill(['unsubscribed_at' => now()])->save();
        if ($subscriber->channel === 'sms') {
            Customer::query()->where('mobile', $subscriber->contact)->update(['newsletter' => false]);
        }
        if (class_exists(Contact::class) && Modules::enabled('Crm')) {
            Contact::query()->where($subscriber->channel === 'sms' ? 'mobile' : 'email', $subscriber->contact)
                ->update(['newsletter_'.$subscriber->channel => false, 'unsubscribed_at' => now()]);
        }
    }

    /** Link for the end of every newsletter message (no login needed, can't be guessed). */
    public static function unsubscribeUrl(NewsletterSubscriber $subscriber): string
    {
        return URL::signedRoute('newsletter.unsubscribe', ['subscriber' => $subscriber->id]);
    }
}
