<?php

/**
 * Khaneh Romantic — Admin: who invited whom, and the rewards
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Loyalty\Filament\Resources\Referrals;

use App\Core\Support\Money;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Loyalty\Filament\Resources\Members\MemberResource;
use Modules\Loyalty\Filament\Resources\Referrals\Pages\ListReferrals;
use Modules\Loyalty\Models\Referral;
use UnitEnum;

class ReferralResource extends Resource
{
    protected static ?string $model = Referral::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    protected static string|UnitEnum|null $navigationGroup = 'باشگاه مشتریان';

    protected static ?int $navigationSort = 50;

    protected static ?string $modelLabel = 'معرفی';

    protected static ?string $pluralModelLabel = 'معرفی دوستان';

    protected static ?string $slug = 'loyalty/referrals';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['referrer', 'referred']))
            ->columns([
                TextColumn::make('referrer.mobile')->label('معرف')->state(fn (Referral $r) => $r->referrer?->displayName())
                    ->url(fn (Referral $r) => MemberResource::getUrl('view', ['record' => $r->referrer_id])),
                TextColumn::make('referred.mobile')->label('دوست معرفی‌شده')->state(fn (Referral $r) => $r->referred?->displayName())
                    ->url(fn (Referral $r) => MemberResource::getUrl('view', ['record' => $r->referred_id])),
                TextColumn::make('status')->label('وضعیت')->badge()
                    ->labels(Referral::STATUSES, Referral::COLORS),
                TextColumn::make('rewards')->label('هدیه‌ها (تومان)')->state(fn (Referral $r) => $r->status === 'pending' ? '—' : Money::toman($r->referrer_reward).' + '.Money::toman($r->referred_reward)),
                TextColumn::make('created_at')->label('عضویت')->jalali()->sortable(),
                TextColumn::make('rewarded_at')->label('اولین خرید')->jalali()->placeholder('—'),
            ])
            ->filters([SelectFilter::make('status')->label('وضعیت')->options(Referral::STATUSES)]);
    }

    public static function getPages(): array
    {
        return ['index' => ListReferrals::route('/')];
    }
}
