<?php

/**
 * Khaneh Romantic — Admin: newsletter members (SMS / email), export, unsubscribe
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Content\Filament\Resources\NewsletterSubscribers;

use App\Core\Support\Jalali;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;
use Modules\Content\Filament\Resources\NewsletterSubscribers\Pages\ListNewsletterSubscribers;
use Modules\Content\Models\NewsletterSubscriber;
use Modules\Content\Support\Newsletter;
use Modules\Crm\Support\Csv;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class NewsletterSubscriberResource extends Resource
{
    protected static ?string $model = NewsletterSubscriber::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|UnitEnum|null $navigationGroup = 'بازاریابی';

    protected static ?int $navigationSort = 60;

    protected static ?string $modelLabel = 'عضو خبرنامه';

    protected static ?string $pluralModelLabel = 'اعضای خبرنامه';

    protected static ?string $navigationLabel = 'خبرنامه';

    protected static ?string $slug = 'newsletter';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('contact')->label('شماره / ایمیل')->searchable()->copyable()
                    ->faDigits()->extraAttributes(['dir' => 'ltr', 'class' => 'justify-end']),
                TextColumn::make('channel')->label('نوع')->badge()->labels(NewsletterSubscriber::CHANNELS)
                    ->color(fn ($state) => $state === 'sms' ? 'info' : 'gray'),
                TextColumn::make('source')->label('از کجا')->labels(NewsletterSubscriber::SOURCES),
                TextColumn::make('created_at')->label('عضویت')->sortable()->jalali(),
                TextColumn::make('unsubscribed_at')->label('وضعیت')->badge()
                    ->state(fn (NewsletterSubscriber $s) => $s->unsubscribed_at ? 'لغو شده' : 'فعال')
                    ->color(fn (NewsletterSubscriber $s) => $s->unsubscribed_at ? 'gray' : 'success'),
            ])
            ->filters([
                SelectFilter::make('channel')->label('نوع')->options(NewsletterSubscriber::CHANNELS),
                TernaryFilter::make('active')->label('وضعیت')->trueLabel('فعال')->falseLabel('لغو شده')->default(true)
                    ->queries(true: fn (Builder $query) => $query->whereNull('unsubscribed_at'), false: fn (Builder $query) => $query->whereNotNull('unsubscribed_at')),
            ])
            ->recordActions([
                Action::make('unsubscribe')->label('لغو عضویت')->icon('heroicon-o-no-symbol')->color('gray')->iconButton()
                    ->visible(fn (NewsletterSubscriber $s) => ! $s->unsubscribed_at)->requiresConfirmation()
                    ->action(fn (NewsletterSubscriber $s) => Newsletter::unsubscribe($s)),
                Action::make('resubscribe')->label('عضویت دوباره')->icon('heroicon-o-arrow-uturn-left')->color('success')->iconButton()
                    ->visible(fn (NewsletterSubscriber $s) => (bool) $s->unsubscribed_at)
                    ->action(fn (NewsletterSubscriber $s) => Newsletter::subscribe($s->contact, $s->source ?: 'admin')),
                Action::make('link')->label('لینک لغو عضویت')->icon('heroicon-o-link')->color('gray')->iconButton()
                    ->modalHeading('لینک لغو عضویت (برای انتهای پیام‌ها)')->modalSubmitAction(false)->modalCancelActionLabel('بستن')
                    ->modalContent(fn (NewsletterSubscriber $s) => new HtmlString('<div dir="ltr" class="break-all rounded-lg bg-gray-50 p-3 font-mono text-xs dark:bg-white/5">'.e(Newsletter::unsubscribeUrl($s)).'</div>')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('export')->label('خروجی CSV')->icon('heroicon-o-arrow-down-tray')
                        ->action(fn (Collection $records) => self::export($records)),
                    BulkAction::make('unsubscribe')->label('لغو عضویت')->icon('heroicon-o-no-symbol')->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each(fn ($s) => Newsletter::unsubscribe($s)))->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /** @param  iterable<NewsletterSubscriber>  $subscribers */
    public static function export(iterable $subscribers): StreamedResponse
    {
        $rows = (function () use ($subscribers) {
            foreach ($subscribers as $s) {
                yield [$s->contact, NewsletterSubscriber::CHANNELS[$s->channel] ?? $s->channel, NewsletterSubscriber::SOURCES[$s->source] ?? $s->source,
                    Jalali::format($s->created_at), $s->unsubscribed_at ? 'لغو شده' : 'فعال', $s->unsubscribed_at ? '' : Newsletter::unsubscribeUrl($s)];
            }
        })();

        return Csv::download('newsletter-'.now()->format('Ymd-His').'.csv', ['شماره/ایمیل', 'نوع', 'از کجا', 'تاریخ عضویت', 'وضعیت', 'لینک لغو عضویت'], $rows);
    }

    public static function getPages(): array
    {
        return ['index' => ListNewsletterSubscribers::route('/')];
    }
}
