<?php

/**
 * Khaneh Romantic — One import step (brands, products, orders…)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Every step is idempotent: records are matched by their WordPress id (legacy_* columns), so
 * running it again updates instead of duplicating. In a dry run nothing is written.
 */

namespace Modules\WordPressImport\Support\Steps;

use App\Core\Support\PersianText;
use Illuminate\Support\Carbon;
use Modules\WordPressImport\Support\Report;
use Modules\WordPressImport\Support\Source;

abstract class Step
{
    public function __construct(protected Source $wp) {}

    abstract public function key(): string;

    abstract public function label(): string;

    /** How many records WordPress has for this step (for the admin page). */
    abstract public function sourceCount(): int;

    /** How many are already here. */
    abstract public function importedCount(): int;

    abstract public function run(Report $report): void;

    /** Modules this step needs switched on. */
    public function requires(): array
    {
        return [];
    }

    /** A WooCommerce amount (IRT, IRR, IRHT, IRHR) → Rials. */
    protected function rials(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $number = (float) str_replace([',', '٬'], '', PersianText::toLatinDigits((string) $value));

        return (int) round($number * match (strtoupper((string) config('modules.wordpressimport.currency', 'IRT'))) {
            'IRR' => 1, 'IRHR' => 1_000, 'IRHT' => 10_000, default => 10,   // Rial, thousand Rials, thousand Tomans, Toman
        });
    }

    protected function date(mixed $value): ?Carbon
    {
        if ($value === null || $value === '' || $value === '0000-00-00 00:00:00' || $value === 0 || $value === '0') {
            return null;
        }

        return is_numeric($value)
            ? Carbon::createFromTimestampUTC((int) $value)
            : Carbon::parse((string) $value, 'UTC');
    }
}
