<?php

/**
 * Khaneh Romantic — Admin «ربات»: who may use the bot (link codes for Telegram and Bale), connection,
 * the shared history
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Bot\Filament\Resources\Operators;

use App\Core\Support\PersianText;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Modules\Bot\Filament\Resources\Operators\Pages\ManageBotOperators;
use Modules\Bot\Models\BotMessage;
use Modules\Bot\Models\BotOperator;
use Modules\Bot\Support\Channel;
use UnitEnum;

class BotOperatorResource extends Resource
{
    protected static ?string $model = BotOperator::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'تنظیمات';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'ربات بله و تلگرام';

    protected static ?string $modelLabel = 'فرد';

    protected static ?string $pluralModelLabel = 'ربات بله و تلگرام';

    protected static ?string $slug = 'bot';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            TextInput::make('name')->label('نام (ربات با این نام صدا می‌زند)')->required()->maxLength(60),
            Select::make('user_id')->label('کاربر پنل (اختیاری)')->options(fn () => User::query()->pluck('name', 'id'))->searchable(),
            Toggle::make('notify_orders')->label('خبر سفارش جدید برایش برود')->default(true),
            Toggle::make('is_active')->label('فعال')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('نام')->weight('bold')->description(fn (BotOperator $o) => $o->user?->name),
                IconColumn::make('bale_chat_id')->label('بله')->boolean()->state(fn (BotOperator $o) => (bool) $o->bale_chat_id),
                IconColumn::make('telegram_chat_id')->label('تلگرام')->boolean()->state(fn (BotOperator $o) => (bool) $o->telegram_chat_id),
                TextColumn::make('drafts_count')->label('پیش‌نویس باز')->counts(['drafts' => fn ($q) => $q->where('status', 'open')])->faDigits(),
                ToggleColumn::make('notify_orders')->label('خبر سفارش'),
                ToggleColumn::make('is_active')->label('فعال'),
                TextColumn::make('last_seen_at')->label('آخرین پیام')->since()->placeholder('—'),
            ])
            ->recordActions([
                self::linkAction(),
                Action::make('history')->label('تاریخچه')->icon('heroicon-o-clock')->color('gray')
                    ->modalHeading(fn (BotOperator $o) => 'آخرین پیام‌های '.$o->name)
                    ->modalSubmitAction(false)->modalCancelActionLabel('بستن')
                    ->modalContent(fn (BotOperator $o) => self::history($o)),
                Action::make('unlink')->label('قطع')->icon('heroicon-o-link-slash')->color('danger')->requiresConfirmation()
                    ->visible(fn (BotOperator $o) => $o->telegram_chat_id || $o->bale_chat_id)
                    ->schema([Select::make('channel')->label('کدام')->options(['bale' => 'بله', 'telegram' => 'تلگرام', 'both' => 'هر دو'])->default('both')->required()])
                    ->action(fn (BotOperator $o, array $data) => $o->update(match ($data['channel']) {
                        'bale' => ['bale_chat_id' => null], 'telegram' => ['telegram_chat_id' => null], default => ['bale_chat_id' => null, 'telegram_chat_id' => null],
                    })),
                EditAction::make()->label(''),
                DeleteAction::make()->label(''),
            ])
            ->emptyStateHeading('هنوز کسی به ربات وصل نیست')
            ->emptyStateDescription('«افزودن فرد» را بزنید، بعد «کد اتصال» را در بله و تلگرام برای ربات بفرستید.');
    }

    /** a one-time code (30 min) + deep links: the person sends it to the bot in Bale and/or Telegram */
    public static function linkAction(): Action
    {
        return Action::make('link')->label('کد اتصال')->icon('heroicon-o-key')->color('primary')
            ->action(function (BotOperator $o) {
                $code = $o->newLinkCode();
                $links = [];
                foreach (Channel::all() as $channel) {
                    $username = ManageBotOperators::username($channel);
                    $url = $username ? ($channel->name === 'telegram' ? 'https://t.me/' : 'https://ble.ir/').$username.'?start='.$code : null;
                    $links[] = '<li>'.$channel->label().': '.($url ? '<a class="text-primary-600 underline" target="_blank" href="'.e($url).'">باز کردن ربات</a> و زدن Start' : 'ربات را باز کنید و کد را بفرستید').'</li>';
                }
                Notification::make()->persistent()->success()
                    ->title('کد اتصال '.$o->name.': '.PersianText::toPersianDigits($code).' ('.$code.')')
                    ->body(new HtmlString('<div class="text-sm leading-7"><p>این کد را در بله و/یا تلگرام برای ربات بفرستید؛ تا ۳۰ دقیقه معتبر است. برای اپ دوم دوباره «کد اتصال» بزنید.</p>'
                        .($links ? '<ul class="list-disc ps-5">'.implode('', $links).'</ul>' : '<p class="text-danger-600">اول توکن ربات را در «تنظیمات فروشگاه ← ربات بله و تلگرام» بگذارید و روشن کنید.</p>').'</div>'))
                    ->send();
            });
    }

    private static function history(BotOperator $operator): HtmlString
    {
        $rows = BotMessage::query()->where('operator_id', $operator->id)->latest('id')->limit(40)->get()->reverse();
        if ($rows->isEmpty()) {
            return new HtmlString('<p class="text-sm text-gray-500">هنوز پیامی نیست.</p>');
        }

        return new HtmlString('<div class="max-h-[60vh] space-y-2 overflow-y-auto text-sm">'.$rows->map(fn (BotMessage $m) => '<div class="rounded-lg p-2 '
            .($m->direction === 'in' ? 'bg-gray-100 dark:bg-white/5' : 'ms-8 bg-primary-50 dark:bg-primary-500/10').'">'
            .'<div class="text-[11px] text-gray-500">'.e(Channel::NAMES[$m->channel] ?? $m->channel).' · '.e(PersianText::toPersianDigits($m->created_at?->format('H:i') ?? '')).'</div>'
            .'<div class="whitespace-pre-line">'.e((string) $m->text).'</div></div>')->implode('').'</div>');
    }

    public static function getPages(): array
    {
        return ['index' => ManageBotOperators::route('/')];
    }
}
