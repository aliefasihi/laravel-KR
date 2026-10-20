<?php

/**
 * Khaneh Romantic — Admin: product reviews (moderation + replies)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Reviews\Filament\Resources\Reviews;

use App\Core\Support\PersianText;
use App\Filament\Support\NavigationBadge;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Reviews\Filament\Resources\Reviews\Pages\ListReviews;
use Modules\Reviews\Models\Review;
use UnitEnum;

class ReviewResource extends Resource
{
    public const BADGE_CACHE_KEY = Review::BADGE_KEY;

    protected static ?string $model = Review::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static string|UnitEnum|null $navigationGroup = 'فروش';

    protected static ?int $navigationSort = 25;

    protected static ?string $modelLabel = 'نظر';

    protected static ?string $pluralModelLabel = 'نظرات محصولات';

    protected static ?string $slug = 'reviews';

    public static function getNavigationBadge(): ?string
    {
        return NavigationBadge::count(self::BADGE_CACHE_KEY, fn () => Review::query()->where('status', 'pending')->count());
    }

    public static function canCreate(): bool
    {
        return false;
    }

    private static function setStatus(Review $review, string $status): void
    {
        $review->update(['status' => $status]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('product'))
            ->columns([
                TextColumn::make('rating')->label('امتیاز')->formatStateUsing(fn (int $state) => str_repeat('★', $state).str_repeat('☆', 5 - $state))
                    ->color('warning')->sortable(),
                TextColumn::make('body')->label('نظر')->wrap()->limit(160)->searchable()
                    ->description(fn (Review $r) => $r->title),
                TextColumn::make('product.name')->label('محصول')->limit(40)->searchable()
                    ->url(fn (Review $r) => $r->product?->url(), shouldOpenInNewTab: true),
                TextColumn::make('name')->label('مشتری')->searchable(['name', 'mobile'])
                    ->description(fn (Review $r) => $r->is_verified_buyer ? 'خریدار این محصول' : ($r->customer_id ? 'مشتری سایت' : ($r->mobile ? 'مهمان · '.PersianText::toPersianDigits($r->mobile) : null))),
                TextColumn::make('helpful')->label('مفید')->faDigits()->sortable()->toggleable(),
                TextColumn::make('status')->label('وضعیت')->badge()
                    ->labels(Review::STATUSES, Review::COLORS),
                IconColumn::make('reply')->label('پاسخ')->boolean()->state(fn (Review $r) => filled($r->reply)),
                TextColumn::make('created_at')->label('تاریخ')->sortable()->jalali(),
            ])
            ->filters([
                SelectFilter::make('status')->label('وضعیت')->options(Review::STATUSES)->default('pending'),
                SelectFilter::make('rating')->label('امتیاز')->options([5 => '۵', 4 => '۴', 3 => '۳', 2 => '۲', 1 => '۱']),
            ])
            ->recordActions([
                Action::make('approve')->label('تأیید')->icon('heroicon-o-check')->color('success')
                    ->visible(fn (Review $r) => $r->status !== 'approved')
                    ->action(fn (Review $r) => self::setStatus($r, 'approved')),
                Action::make('reject')->label('رد')->icon('heroicon-o-x-mark')->color('gray')
                    ->visible(fn (Review $r) => $r->status !== 'rejected')
                    ->action(fn (Review $r) => self::setStatus($r, 'rejected')),
                Action::make('reply')->label('پاسخ')->icon('heroicon-o-chat-bubble-left')
                    ->fillForm(fn (Review $r) => ['reply' => $r->reply])
                    ->schema([Textarea::make('reply')->label('پاسخ فروشگاه (زیر نظر نمایش داده می‌شود)')->rows(4)])
                    ->action(fn (Review $r, array $data) => $r->update(['reply' => $data['reply'] ?: null, 'replied_at' => $data['reply'] ? now() : null, 'status' => 'approved'])),
                DeleteAction::make()->label(''),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve')->label('تأیید')->icon('heroicon-o-check')
                        ->action(fn (Collection $records) => $records->each(fn (Review $r) => self::setStatus($r, 'approved')))->deselectRecordsAfterCompletion(),
                    BulkAction::make('reject')->label('رد')->icon('heroicon-o-x-mark')
                        ->action(fn (Collection $records) => $records->each(fn (Review $r) => self::setStatus($r, 'rejected')))->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListReviews::route('/')];
    }
}
