<?php

/**
 * Khaneh Romantic — Shared admin column/entry formats, so every table shows dates, numbers,
 * money and statuses the same way:
 *
 *   TextColumn::make('created_at')->jalali()            ۲۵ مهر ۱۴۰۵
 *   TextColumn::make('paid_at')->jalali(time: true)     ۲۵ مهر ۱۴۰۵، ۱۴:۳۰
 *   TextColumn::make('likes')->faDigits()               ۱۲
 *   TextColumn::make('total')->toman(unit: true)        ۱٬۲۰۰٬۰۰۰ تومان
 *   TextColumn::make('status')->labels(Order::STATUSES, Order::COLORS)   badge with label and colour
 *
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace App\Filament\Support;

use App\Core\Support\Jalali;
use App\Core\Support\Money;
use App\Core\Support\PersianText;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;

final class Formats
{
    public static function register(): void
    {
        foreach ([TextColumn::class, TextEntry::class] as $component) {
            $component::macro('jalali', function (bool $time = false) {
                return $this->formatStateUsing(fn ($state) => $state ? ($time ? Jalali::dateTime($state) : Jalali::format($state)) : null);
            });

            $component::macro('faDigits', function () {
                return $this->formatStateUsing(fn ($state) => $state === null || $state === '' ? null : PersianText::toPersianDigits((string) $state));
            });

            $component::macro('toman', function (bool $unit = false) {
                return $this->formatStateUsing(fn ($state) => Money::toman((int) $state).($unit ? ' تومان' : ''));
            });

            $component::macro('labels', function (array $labels, ?array $colors = null) {
                $this->formatStateUsing(fn ($state) => $labels[$state] ?? $state);

                return $colors === null ? $this : $this->badge()->color(fn ($state) => $colors[$state] ?? 'gray');
            });
        }
    }
}
