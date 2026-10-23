<?php

/**
 * Khaneh Romantic — Admin: notification campaigns (write, send now or schedule, test on one customer, stats)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Push\Filament\Resources\Campaigns;

use App\Core\Support\Jalali;
use App\Core\Support\PersianText;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Customer\Models\Customer;
use Modules\Customer\Support\Mobile;
use Modules\Push\Filament\Resources\Campaigns\Pages\CreatePushCampaign;
use Modules\Push\Filament\Resources\Campaigns\Pages\EditPushCampaign;
use Modules\Push\Filament\Resources\Campaigns\Pages\ListPushCampaigns;
use Modules\Push\Models\PushCampaign;
use Modules\Push\Models\PushSubscription;
use Modules\Push\Support\Push;
use UnitEnum;

class PushCampaignResource extends Resource
{
    protected static ?string $model = PushCampaign::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static string|UnitEnum|null $navigationGroup = 'بازاریابی';

    protected static ?int $navigationSort = 47;

    protected static ?string $modelLabel = 'نوتیفیکیشن';

    protected static ?string $pluralModelLabel = 'نوتیفیکیشن‌ها';

    protected static ?string $slug = 'push';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('پیام')->columns(2)->columnSpanFull()->schema([
                TextInput::make('title')->label('عنوان')->required()->maxLength(60)->placeholder('حراج یلدا شروع شد 🍉'),
                TextInput::make('url')->label('لینک (با کلیک باز می‌شود)')->maxLength(250)->placeholder('/shop?sale=1')
                    ->extraInputAttributes(['dir' => 'ltr'])->helperText('فقط صفحه‌های همین سایت. خالی = صفحه‌ی اصلی.'),
                Textarea::make('body')->label('متن')->rows(2)->maxLength(160)->columnSpanFull()
                    ->placeholder('تا ۳۰٪ تخفیف روی سرویس‌های پذیرایی؛ فقط تا پنجشنبه.'),
                FileUpload::make('image')->label('عکس بزرگ (اختیاری)')->image()->disk('public')->directory('push')->visibility('public')
                    ->maxSize(1024)->helperText('اندروید و کامپیوتر نشانش می‌دهند؛ نسبت ۲ به ۱، مثلاً ۷۲۰×۳۶۰.'),
                Section::make()->schema([
                    Select::make('audience')->label('برای')->options(PushCampaign::AUDIENCES)->default('all')->required()->native(false)
                        ->helperText(fn () => 'الان '.PersianText::toPersianDigits(PushSubscription::query()->count()).' مرورگر عضو است.'),
                    DateTimePicker::make('scheduled_at')->label('زمان ارسال (اختیاری)')->helperText('خالی = با دکمه‌ی «ارسال» همان لحظه.'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('title')->label('پیام')->weight('bold')->description(fn (PushCampaign $c) => $c->body)->wrap(),
                TextColumn::make('audience')->label('برای')->formatStateUsing(fn ($state) => PushCampaign::AUDIENCES[$state] ?? $state),
                TextColumn::make('status')->label('وضعیت')->badge()->labels(PushCampaign::STATUSES, PushCampaign::COLORS)
                    ->description(fn (PushCampaign $c) => $c->status === 'scheduled' && $c->scheduled_at ? Jalali::format($c->scheduled_at).' '.PersianText::toPersianDigits($c->scheduled_at->format('H:i')) : null),
                TextColumn::make('delivered')->label('رسید')->state(fn (PushCampaign $c) => PersianText::toPersianDigits($c->delivered).' از '.PersianText::toPersianDigits($c->total))
                    ->description(fn (PushCampaign $c) => $c->failed ? PersianText::toPersianDigits($c->failed).' ناموفق' : null),
                TextColumn::make('clicks')->label('کلیک')->state(fn (PushCampaign $c) => PersianText::toPersianDigits($c->clicks)
                    .($c->delivered ? ' ('.PersianText::toPersianDigits((string) round($c->clicks * 100 / $c->delivered, 1)).'٪)' : '')),
                TextColumn::make('sent_at')->label('ارسال')->jalali()->placeholder('—'),
            ])
            ->recordActions([
                self::sendAction(),
                Action::make('cancel')->label('لغو')->icon('heroicon-o-x-mark')->color('gray')
                    ->visible(fn (PushCampaign $c) => $c->status === 'scheduled')
                    ->action(fn (PushCampaign $c) => $c->update(['status' => 'draft'])),
                EditAction::make()->label('')->visible(fn (PushCampaign $c) => in_array($c->status, ['draft', 'scheduled'], true)),
                DeleteAction::make()->label('')->visible(fn (PushCampaign $c) => $c->status !== 'sending'),
            ]);
    }

    public static function sendAction(): Action
    {
        return Action::make('send')->label(fn (PushCampaign $c) => $c->scheduled_at?->isFuture() ? 'زمان‌بندی' : 'ارسال')
            ->icon('heroicon-o-paper-airplane')->color('primary')
            ->visible(fn (PushCampaign $c) => $c->status === 'draft')
            ->requiresConfirmation()
            ->modalDescription(fn (PushCampaign $c) => $c->scheduled_at?->isFuture()
                ? 'در زمان تعیین‌شده ارسال می‌شود.'
                : 'برای '.PersianText::toPersianDigits(self::audienceCount($c->audience)).' مرورگر فرستاده می‌شود (در پس‌زمینه، طی چند دقیقه).')
            ->action(function (PushCampaign $c) {
                if (! Push::enabled()) {
                    Notification::make()->danger()->title('اول نوتیفیکیشن را در «تنظیمات ← نوتیفیکیشن» روشن کنید')->send();

                    return;
                }
                if ($c->scheduled_at?->isFuture()) {
                    $c->update(['status' => 'scheduled']);
                    Notification::make()->success()->title('زمان‌بندی شد')->send();

                    return;
                }
                $total = app(Push::class)->launch($c);
                Notification::make()->success()->title('در صف ارسال: '.PersianText::toPersianDigits($total).' مرورگر')->send();
            });
    }

    /** «ارسال آزمایشی»: to the browsers of one customer (e.g. yourself, logged in on the site with your mobile) */
    public static function testAction(): Action
    {
        return Action::make('test')->label('ارسال آزمایشی')->icon('heroicon-o-beaker')->color('gray')
            ->schema([
                TextInput::make('mobile')->label('موبایل مشتری')->required()->helperText('با همین شماره در سایت وارد شوید و نوتیفیکیشن را روشن کنید.'),
                TextInput::make('title')->label('عنوان')->default('سلام از '.config('khaneh.store.name'))->required(),
            ])
            ->action(function (array $data) {
                $customer = Customer::query()->where('mobile', Mobile::normalize(PersianText::toLatinDigits($data['mobile'])))->first();
                $count = $customer ? app(Push::class)->toCustomer($customer->id, ['title' => $data['title'], 'body' => 'این یک پیام آزمایشی است.', 'url' => '/']) : 0;
                $count
                    ? Notification::make()->success()->title('در صف: '.PersianText::toPersianDigits($count).' مرورگر — تا یک دقیقه‌ی دیگر می‌رسد')->send()
                    : Notification::make()->warning()->title('این مشتری هیچ مرورگر عضوی ندارد')->send();
            });
    }

    public static function audienceCount(string $audience): int
    {
        return PushSubscription::query()
            ->when($audience === 'customers', fn ($q) => $q->whereNotNull('customer_id'))
            ->when($audience === 'guests', fn ($q) => $q->whereNull('customer_id'))
            ->count();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPushCampaigns::route('/'),
            'create' => CreatePushCampaign::route('/create'),
            'edit' => EditPushCampaign::route('/{record}/edit'),
        ];
    }
}
