<?php

/**
 * Khaneh Romantic — The occasions and which one is on right now
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Switched on by hand in تنظیمات ← مناسبت‌ها (one at a time), or — when «خودکار» is on and none is
 * switched on by hand — by the calendar: each occasion knows its own days (Jalali, Gregorian or,
 * for روز مادر, the lunar calendar). Admins can preview any of them: /?occasion=yalda (?occasion=off).
 *
 * For now the occasions dress demo 1 (the glass design) only; on other demos nothing changes.
 */

namespace Modules\Occasions\Support;

use App\Core\Settings\Settings;
use App\Core\Support\Designs;
use App\Core\Support\Jalali;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use IntlDateFormatter;
use Throwable;

final class Occasions
{
    public const SESSION_KEY = 'occasion.preview';

    /**
     * accent: buttons/links (rose replaced) · promo/amazing: CSS backgrounds · bits: [art, weight]
     * motion: fall (drift in from the sides and down) · rise (sparks up from below) · fly (across, wings flapping)
     */
    public const ALL = [
        'yalda' => [
            'label' => 'شب یلدا', 'when' => '۲۸ آذر تا ۱ دی',
            'accent' => ['#c41e3a', '#a3162f', '#fde2e6'],
            'promo_bg' => 'linear-gradient(to left, #7d0c22, #c41e3a 55%, #e0566b)',
            'amazing_bg' => 'linear-gradient(to left, #5c0a1b, #a3162f 45%, #d9465c)',
            'promo' => 'یلدا مبارک 🍉 شب‌چله را با تخفیف‌های یلدایی خانه رمانتیک گرم کنید',
            'amazing' => 'شگفت‌انگیز یلدایی', 'search' => 'یه فال حافظ بگیر… یا دنبال هدیه‌ی یلدا بگرد', 'badge' => 'یلدایی',
            'emblem' => 'pomegranate', 'hero' => 'pomegranate-open', 'header' => ['pomegranate', 'candle'],
            'bits' => [['seed', 6], ['pomegranate', 1]], 'motion' => 'fall',
        ],
        'chaharshanbe' => [
            'label' => 'چهارشنبه‌سوری', 'when' => '۲۰ اسفند تا آخرین چهارشنبه‌ی سال',
            'accent' => ['#ea580c', '#c2410c', '#ffedd5'],
            'promo_bg' => 'linear-gradient(to left, #7c2d12, #ea580c 55%, #f59e0b)',
            'amazing_bg' => 'linear-gradient(to left, #431407, #9a3412 45%, #f97316)',
            'promo' => 'زردی من از تو، سرخی تو از من 🔥 تخفیف‌های چهارشنبه‌سوری',
            'amazing' => 'شگفت‌انگیز چهارشنبه‌سوری', 'search' => 'از روی آتیش بپر… و هدیه‌ات رو پیدا کن', 'badge' => 'آتیشی',
            'emblem' => 'flame', 'hero' => 'bonfire', 'header' => ['flame', 'flame'],
            'bits' => [['spark', 1]], 'motion' => 'rise',
        ],
        'nowruz' => [
            'label' => 'نوروز', 'when' => '۲۵ اسفند تا ۱۲ فروردین',
            'accent' => ['#16a34a', '#15803d', '#dcfce7'],
            'promo_bg' => 'linear-gradient(to left, #166534, #16a34a 50%, #f472b6)',
            'amazing_bg' => 'linear-gradient(to left, #14532d, #16a34a 50%, #f9a8d4)',
            'promo' => 'نوروزتان پیروز 🌱 سفره‌ی هفت‌سین و خانه‌ی نو با تخفیف‌های نوروزی',
            'amazing' => 'شگفت‌انگیز نوروزی', 'search' => 'برای هفت‌سین و عیدی‌ها جستجو کنید…', 'badge' => 'نوروزی',
            'emblem' => 'goldfish', 'hero' => 'sabzeh', 'header' => ['goldfish', 'egg'],
            'bits' => [['blossom', 5], ['leaf', 2]], 'motion' => 'fall',
        ],
        'sizdah' => [
            'label' => 'سیزده‌بدر', 'when' => '۱۲ و ۱۳ فروردین',
            'accent' => ['#65a30d', '#4d7c0f', '#ecfccb'],
            'promo_bg' => 'linear-gradient(to left, #3f6212, #65a30d 55%, #38bdf8)',
            'amazing_bg' => 'linear-gradient(to left, #365314, #4d7c0f 45%, #0ea5e9)',
            'promo' => 'سیزده‌تون به در 🌿 برای طبیعت‌گردی، ظرف و وسایل پیک‌نیک با تخفیف',
            'amazing' => 'شگفت‌انگیز سیزده‌بدر', 'search' => 'سبزه گره بزن… و وسایل پیک‌نیک رو پیدا کن', 'badge' => 'سیزده‌بدری',
            'emblem' => 'knot', 'hero' => 'kite', 'header' => ['kite', 'knot'],
            'bits' => [['leaf', 4], ['grass', 3]], 'motion' => 'fall',
        ],
        'sepandarmazgan' => [
            'label' => 'سپندارمذگان و ولنتاین', 'when' => '۲۱ تا ۲۹ بهمن',
            'accent' => ['#e11d48', '#be123c', '#ffe4e6'],
            'promo_bg' => 'linear-gradient(to left, #9f1239, #e11d48 55%, #f472b6)',
            'amazing_bg' => 'linear-gradient(to left, #881337, #e11d48 50%, #f9a8d4)',
            'promo' => 'روز عشق مبارک ❤️ هدیه‌ای برای خانه‌ی مشترکتان',
            'amazing' => 'شگفت‌انگیز روز عشق', 'search' => 'یه هدیه‌ی عاشقانه پیدا کن…', 'badge' => 'عاشقانه',
            'emblem' => 'heart', 'hero' => 'heart', 'header' => ['heart', 'heart-pink'],
            'bits' => [['heart', 3], ['heart-pink', 3]], 'motion' => 'fall',
        ],
        'mothers' => [
            'label' => 'روز مادر', 'when' => '۳ روز مانده تا ۲۰ جمادی‌الثانی',
            'accent' => ['#db2777', '#be185d', '#fce7f3'],
            'promo_bg' => 'linear-gradient(to left, #9d174d, #db2777 55%, #f9a8d4)',
            'amazing_bg' => 'linear-gradient(to left, #831843, #db2777 50%, #fbcfe8)',
            'promo' => 'روز مادر مبارک 🌷 هدیه‌ای که هر روز در خانه‌اش می‌بیند',
            'amazing' => 'شگفت‌انگیز روز مادر', 'search' => 'هدیه‌ی روز مادر رو اینجا پیدا کن…', 'badge' => 'هدیه‌ی مادر',
            'emblem' => 'tulip', 'hero' => 'tulip', 'header' => ['tulip', 'heart-pink'],
            'bits' => [['petal', 5], ['tulip', 1]], 'motion' => 'fall',
        ],
        'halloween' => [
            'label' => 'هالووین', 'when' => '۲۵ تا ۳۱ اکتبر',
            'accent' => ['#ea580c', '#c2410c', '#ffedd5'],
            'promo_bg' => 'linear-gradient(to left, #1e1b4b, #4c1d95 50%, #ea580c)',
            'amazing_bg' => 'linear-gradient(to left, #1e1b2e, #4c1d95 50%, #ea580c)',
            'promo' => 'هالووین 🎃 تخفیف‌های ترسناک، فقط همین چند روز',
            'amazing' => 'شگفت‌انگیز هالووین', 'search' => 'بگرد… اگه جرئتشو داری 🦇', 'badge' => 'هالووینی',
            'emblem' => 'pumpkin', 'hero' => 'pumpkin', 'header' => ['web', 'bat'],
            'bits' => [['bat', 1]], 'motion' => 'fly',
        ],
        'blackfriday' => [
            'label' => 'بلک فرایدی', 'when' => 'یک هفته تا دوشنبه‌ی بعد از آخرین جمعه‌ی نوامبر',
            'accent' => ['#111827', '#000000', '#fef9c3'],
            'promo_bg' => 'linear-gradient(to left, #000, #111827 60%, #3f3f46)',
            'amazing_bg' => 'linear-gradient(to left, #000, #111827 55%, #27272a)',
            'promo' => 'BLACK FRIDAY ⚡ بزرگ‌ترین حراج سال خانه رمانتیک شروع شد',
            'amazing' => 'شگفت‌انگیز بلک فرایدی', 'search' => 'بزرگ‌ترین تخفیف‌ها رو اینجا پیدا کن…', 'badge' => 'BLACK FRIDAY',
            'emblem' => 'tag', 'hero' => 'bag', 'header' => ['tag-yellow', 'tag'],
            'bits' => [['tag', 3], ['tag-yellow', 3]], 'motion' => 'fall',
        ],
        'christmas' => [
            'label' => 'کریسمس و سال نوی میلادی', 'when' => '۲۰ دسامبر تا ۱ ژانویه',
            'accent' => ['#b91c1c', '#991b1b', '#fee2e2'],
            'promo_bg' => 'linear-gradient(to left, #14532d, #15803d 45%, #b91c1c)',
            'amazing_bg' => 'linear-gradient(to left, #14532d, #166534 45%, #b91c1c)',
            'promo' => 'سال نو مبارک ❄️ هدیه‌های زمستانی و تزئین خانه با تخفیف',
            'amazing' => 'شگفت‌انگیز زمستانی', 'search' => 'هدیه‌ی سال نو رو پیدا کن…', 'badge' => 'سال نو',
            'emblem' => 'ornament', 'hero' => 'tree', 'header' => ['snowflake', 'ornament'],
            'bits' => [['snowflake', 1]], 'motion' => 'fall',
        ],
    ];

    /** @var array<string, mixed>|null|false resolved once per request (false = not yet) */
    private static array|null|false $resolved = false;

    public static function reset(): void
    {
        self::$resolved = false;
    }

    /** The occasion dressing this request: its data + key and the texts set in the panel, or null. */
    public static function current(?Request $request = null): ?array
    {
        if (self::$resolved !== false) {
            return self::$resolved;
        }

        $previewing = self::preview($request);
        $key = $previewing ?? self::active();
        if ($key === 'off' || $key === null || ! isset(self::ALL[$key]) || ! self::supported()) {
            return self::$resolved = null;
        }

        $occasion = self::ALL[$key];

        return self::$resolved = ['key' => $key] + $occasion + [
            'promo_text' => (string) (Settings::get('occasions.'.$key.'_promo') ?: $occasion['promo']),
            'amazing_title' => (string) (Settings::get('occasions.'.$key.'_amazing') ?: $occasion['amazing']),
            'previewing' => $previewing !== null,
        ];
    }

    /** Switched on by hand, otherwise (if «خودکار» is on) the one whose days these are. */
    public static function active(?Carbon $now = null): ?string
    {
        foreach (array_keys(self::ALL) as $key) {
            if (Settings::get('occasions.'.$key)) {
                return $key;
            }
        }

        return Settings::get('occasions.auto') ? self::byDate($now ?? now()) : null;
    }

    /** Which occasion's days contain this moment (Tehran time); shorter windows win. */
    public static function byDate(Carbon $now): ?string
    {
        $local = $now->copy()->setTimezone(config('khaneh.display_timezone', 'Asia/Tehran'));
        [$jy, $jm, $jd] = Jalali::fromGregorian($local->year, $local->month, $local->day);
        $j = $jm * 100 + $jd;          // e.g. 928 = ۲۸ آذر
        $g = $local->month * 100 + $local->day;

        // the last Wednesday of Esfand (Tuesday night is چهارشنبه‌سوری)
        $lastWed = null;
        for ($d = 30; $d >= 20; $d--) {
            [$gy, $gm, $gd] = Jalali::toGregorian($jy, 12, $d);
            if (checkdate($gm, $gd, $gy) && Jalali::fromGregorian($gy, $gm, $gd) === [$jy, 12, $d] && Carbon::create($gy, $gm, $gd)->isWednesday()) {
                $lastWed = 1200 + $d;
                break;
            }
        }

        // Black Friday: the fourth Friday of November, from the Saturday before to Cyber Monday
        $blackFriday = Carbon::create($local->year, 11, 1)->nthOfMonth(4, Carbon::FRIDAY);

        return match (true) {
            $j >= 112 && $j <= 113 => 'sizdah',
            $lastWed !== null && $j >= 1220 && $j <= $lastWed => 'chaharshanbe',
            $j >= 1225 || $j <= 111 => 'nowruz',
            $j >= 928 && $j <= 1001 => 'yalda',
            $j >= 1121 && $j <= 1129 => 'sepandarmazgan',
            self::isMothersDay($local) => 'mothers',
            $local->between($blackFriday->copy()->subDays(6)->startOfDay(), $blackFriday->copy()->addDays(3)->endOfDay()) => 'blackfriday',
            $g >= 1025 && $g <= 1031 => 'halloween',
            $g >= 1220 || $g <= 101 => 'christmas',
            default => null,
        };
    }

    /** Iranian Mother's Day: 20 Jumada al-Thani (lunar) — and the three days before it. */
    private static function isMothersDay(Carbon $local): bool
    {
        if (! class_exists(IntlDateFormatter::class)) {
            return false;
        }
        try {
            $formatter = new IntlDateFormatter('en_US@calendar=islamic-umalqura', IntlDateFormatter::NONE, IntlDateFormatter::NONE, 'Asia/Tehran', IntlDateFormatter::TRADITIONAL, 'M-d');
            foreach ([0, 1, 2, 3] as $ahead) {
                if ($formatter->format($local->copy()->addDays($ahead)->setTime(12, 0)) === '6-20') {
                    return true;
                }
            }
        } catch (Throwable) {
            return false;
        }

        return false;
    }

    /** For now: the glass design (demo 1) — header, home and palette must be the classic ones. */
    public static function supported(): bool
    {
        return Designs::current('theme') === 'classic' && Designs::current('chrome') === 'classic' && Designs::current('home') === 'classic';
    }

    /** ?occasion=yalda (admins only) previews it for this browser; ?occasion=off previews «none»; ?occasion=live ends it. */
    private static function preview(?Request $request): ?string
    {
        $request ??= request();
        if (! $request->hasSession() || ! $request->user('web')?->is_admin) {
            return null;
        }
        $asked = $request->query('occasion');
        if (is_string($asked)) {
            isset(self::ALL[$asked]) || $asked === 'off'
                ? $request->session()->put(self::SESSION_KEY, $asked)
                : $request->session()->forget(self::SESSION_KEY);
        }
        $key = $request->session()->get(self::SESSION_KEY);

        return is_string($key) ? $key : null;
    }
}
