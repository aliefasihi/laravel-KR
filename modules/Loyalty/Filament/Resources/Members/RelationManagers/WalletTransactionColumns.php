<?php

/**
 * Khaneh Romantic — Wallet / points ledger columns shared by the admin tables
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Loyalty\Filament\Resources\Members\RelationManagers;

use App\Core\Support\Jalali;
use App\Core\Support\Money;
use App\Core\Support\PersianText;
use Filament\Tables\Columns\TextColumn;
use Modules\Loyalty\Models\PointsTransaction;
use Modules\Loyalty\Models\WalletTransaction;
use Modules\Loyalty\Support\Loyalty;

final class WalletTransactionColumns
{
    public static function make(bool $withCustomer = false): array
    {
        return array_values(array_filter([
            TextColumn::make('created_at')->label('تاریخ')->sortable()->formatStateUsing(fn ($state) => Jalali::format($state))
                ->description(fn (WalletTransaction $t) => PersianText::toPersianDigits($t->created_at->copy()->setTimezone(config('khaneh.display_timezone'))->format('H:i'))),
            $withCustomer ? TextColumn::make('customer.mobile')->label('مشتری')->state(fn (WalletTransaction $t) => $t->customer?->displayName() ?? 'حذف‌شده')
                ->searchable(query: fn ($query, string $search) => $query->whereHas('customer', fn ($c) => $c->where('mobile', 'like', '%'.PersianText::toLatinDigits($search).'%')->orWhere('last_name', 'like', "%{$search}%"))) : null,
            TextColumn::make('reason')->label('بابت')->badge()->color('gray')->formatStateUsing(fn ($state) => WalletTransaction::REASONS[$state] ?? $state),
            TextColumn::make('description')->label('توضیح')->limit(50)->placeholder('—')
                ->description(fn (WalletTransaction $t) => $t->order_id ? 'سفارش #'.$t->order_id : null),
            TextColumn::make('amount')->label('مبلغ (تومان)')->state(fn (WalletTransaction $t) => ($t->type === 'credit' ? '+' : '−').Money::toman($t->amount))
                ->color(fn (WalletTransaction $t) => $t->type === 'credit' ? 'success' : 'danger'),
            TextColumn::make('balance_after')->label('مانده')->formatStateUsing(fn ($state) => Money::toman((int) $state)),
        ]));
    }

    public static function points(bool $withCustomer = false): array
    {
        return array_values(array_filter([
            TextColumn::make('created_at')->label('تاریخ')->sortable()->formatStateUsing(fn ($state) => Jalali::format($state)),
            $withCustomer ? TextColumn::make('customer.mobile')->label('مشتری')->state(fn (PointsTransaction $t) => $t->customer?->displayName() ?? 'حذف‌شده')
                ->searchable(query: fn ($query, string $search) => $query->whereHas('customer', fn ($c) => $c->where('mobile', 'like', '%'.PersianText::toLatinDigits($search).'%')->orWhere('last_name', 'like', "%{$search}%"))) : null,
            TextColumn::make('type')->label('نوع')->badge()->color('gray')->formatStateUsing(fn ($state) => PointsTransaction::TYPES[$state] ?? $state),
            TextColumn::make('description')->label('توضیح')->limit(50)->placeholder('—'),
            TextColumn::make('amount')->label('امتیاز')->state(fn (PointsTransaction $t) => ($t->amount > 0 ? '+' : '−').Loyalty::fa((int) abs($t->amount)))
                ->color(fn (PointsTransaction $t) => $t->amount > 0 ? 'success' : 'danger'),
            TextColumn::make('balance_after')->label('مانده')->formatStateUsing(fn ($state) => Loyalty::fa((int) $state)),
        ]));
    }
}
