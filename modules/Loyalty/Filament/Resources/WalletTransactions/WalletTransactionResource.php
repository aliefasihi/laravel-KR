<?php

/**
 * Khaneh Romantic — Admin: every wallet movement (read-only ledger) with export
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Loyalty\Filament\Resources\WalletTransactions;

use App\Core\Support\Jalali;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Crm\Support\Csv;
use Modules\Loyalty\Filament\Resources\Members\RelationManagers\WalletTransactionColumns;
use Modules\Loyalty\Filament\Resources\WalletTransactions\Pages\ListWalletTransactions;
use Modules\Loyalty\Models\WalletTransaction;
use UnitEnum;

class WalletTransactionResource extends Resource
{
    protected static ?string $model = WalletTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected static string|UnitEnum|null $navigationGroup = 'باشگاه مشتریان';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'تراکنش کیف پول';

    protected static ?string $pluralModelLabel = 'تراکنش‌های کیف پول';

    protected static ?string $slug = 'loyalty/wallet';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('customer'))
            ->columns(WalletTransactionColumns::make(withCustomer: true))
            ->filters([
                SelectFilter::make('type')->label('نوع')->options(['credit' => 'افزایش', 'debit' => 'کاهش']),
                SelectFilter::make('reason')->label('بابت')->options(WalletTransaction::REASONS),
                Filter::make('date')->schema([DatePicker::make('from')->label('از'), DatePicker::make('until')->label('تا')])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                        ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))),
            ])
            ->toolbarActions([
                BulkAction::make('export')->label('خروجی CSV')->icon('heroicon-o-arrow-down-tray')
                    ->action(fn (Collection $records) => self::export($records->load('customer'))),
            ]);
    }

    /** @param  iterable<WalletTransaction>  $rows */
    public static function export(iterable $rows)
    {
        $data = (function () use ($rows) {
            foreach ($rows as $t) {
                yield [$t->id, Jalali::format($t->created_at), $t->customer?->mobile, $t->customer?->fullName(), $t->type === 'credit' ? 'افزایش' : 'کاهش',
                    intdiv($t->amount, 10), intdiv($t->balance_after, 10), $t->reasonLabel(), $t->order_id, $t->description];
            }
        })();

        return Csv::download('wallet-'.now()->format('Ymd-His').'.csv', ['شناسه', 'تاریخ', 'موبایل', 'نام', 'نوع', 'مبلغ (تومان)', 'مانده (تومان)', 'بابت', 'سفارش', 'توضیح'], $data);
    }

    public static function getPages(): array
    {
        return ['index' => ListWalletTransactions::route('/')];
    }
}
