<?php

/**
 * Khaneh Romantic — Inbox helpers shared by the admin tables and exports
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Crm\Support;

use App\Core\Support\Jalali;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Crm\Models\FormSubmission;

final class Inbox
{
    public const BADGE_KEY = 'admin.badge.inbox';

    public static function newCount(): int
    {
        return (int) rescue(fn () => Cache::remember(self::BADGE_KEY, 60, fn () => FormSubmission::query()->where('status', 'new')->count()), 0, report: false);
    }

    public static function forgetBadge(): void
    {
        Cache::forget(self::BADGE_KEY);
    }

    public static function setStatus(FormSubmission $submission, string $status): void
    {
        $submission->forceFill([
            'status' => $status,
            'handled_by' => in_array($status, ['done', 'spam'], true) ? auth()->id() : $submission->handled_by,
            'handled_at' => in_array($status, ['done', 'spam'], true) ? now() : $submission->handled_at,
        ])->save();
        self::forgetBadge();
    }

    public static function formLabel(string $form): string
    {
        return Forms::get($form)?->label ?? $form;
    }

    /** "نام: علی · پیام: سلام…" for the «all» tab and SMS */
    public static function summary(FormSubmission $submission, int $limit = 140): string
    {
        $fields = Forms::get($submission->form)?->fields ?? [];

        return Str::limit(collect($submission->data ?? [])
            ->filter(fn ($v) => is_scalar($v) && trim((string) $v) !== '')
            ->map(fn ($v, $k) => (isset($fields[$k]) ? $fields[$k].': ' : '').$v)
            ->implode(' · '), $limit);
    }

    /**
     * @param  iterable<FormSubmission>  $submissions
     * @return array{0: list<string>, 1: \Generator}
     */
    public static function export(iterable $submissions, ?string $form): array
    {
        $fields = $form ? (Forms::get($form)?->fields ?? []) : [];
        $headers = ['شناسه', 'تاریخ', 'ساعت', 'فرم', 'نام', 'موبایل', 'ایمیل', 'وضعیت', ...array_values($fields)];
        if (! $form) {
            $headers[] = 'اطلاعات';
        }
        array_push($headers, 'صفحه', 'منبع (utm_source)', 'کمپین (utm_campaign)');

        $rows = (function () use ($submissions, $fields, $form) {
            foreach ($submissions as $s) {
                $local = $s->created_at->copy()->setTimezone(config('khaneh.display_timezone', 'Asia/Tehran'));
                $row = [$s->id, Jalali::format($s->created_at), $local->format('H:i'), self::formLabel($s->form),
                    $s->contact?->name, $s->contact?->mobile, $s->contact?->email, FormSubmission::STATUSES[$s->status] ?? $s->status];
                foreach (array_keys($fields) as $key) {
                    $row[] = $s->data[$key] ?? '';
                }
                if (! $form) {
                    $row[] = self::summary($s, 2000);
                }
                array_push($row, $s->page_url, $s->utm['utm_source'] ?? '', $s->utm['utm_campaign'] ?? '');
                yield $row;
            }
        })();

        return [$headers, $rows];
    }
}
