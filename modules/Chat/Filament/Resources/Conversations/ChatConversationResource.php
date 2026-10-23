<?php

/**
 * Khaneh Romantic — Admin: site chat conversations (who asked what, what the assistant answered,
 * who wants a person, what it couldn't answer → a ready answer in one click)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Chat\Filament\Resources\Conversations;

use App\Core\Support\PersianText;
use App\Filament\Support\NavigationBadge;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Modules\Chat\Filament\Resources\Conversations\Pages\ListChatConversations;
use Modules\Chat\Filament\Resources\Conversations\Pages\ViewChatConversation;
use Modules\Chat\Filament\Resources\Faqs\ChatFaqResource;
use Modules\Chat\Models\ChatConversation;
use Modules\Chat\Models\ChatFaq;
use Modules\Chat\Models\ChatMessage;
use UnitEnum;

class ChatConversationResource extends Resource
{
    protected static ?string $model = ChatConversation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleOvalLeftEllipsis;

    protected static string|UnitEnum|null $navigationGroup = 'ارتباط با مشتری';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'گفتگو';

    protected static ?string $pluralModelLabel = 'گفتگوهای چت';

    protected static ?string $slug = 'chats';

    public static function getNavigationBadge(): ?string
    {
        return NavigationBadge::count(ChatConversation::BADGE_KEY, fn () => ChatConversation::query()
            ->where('handoff', true)->whereNotNull('mobile')->where('created_at', '>=', now()->subDays(2))->count());
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('last_message_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('customer')->where('messages_count', '>', 0))
            ->columns([
                TextColumn::make('first')->label('اولین پیام')->wrap()->weight('bold')
                    ->state(fn (ChatConversation $c) => mb_substr((string) $c->messages()->where('role', 'user')->value('text'), 0, 90))
                    ->description(fn (ChatConversation $c) => $c->page),
                TextColumn::make('who')->label('مشتری')->state(fn (ChatConversation $c) => $c->customer?->displayName() ?? ($c->mobile ? PersianText::toPersianDigits($c->mobile) : 'مهمان')),
                TextColumn::make('messages_count')->label('پیام')->faDigits(),
                IconColumn::make('handoff')->label('پشتیبان خواست')->boolean()->trueIcon('heroicon-o-hand-raised')->trueColor('warning')->falseIcon('heroicon-o-minus')->falseColor('gray'),
                IconColumn::make('unanswered')->label('بی‌جواب')->boolean()->trueIcon('heroicon-o-question-mark-circle')->trueColor('danger')->falseIcon('heroicon-o-minus')->falseColor('gray'),
                TextColumn::make('last_message_at')->label('آخرین پیام')->since()->sortable(),
            ])
            ->filters([
                Filter::make('handoff')->label('پشتیبان خواسته‌اند')->query(fn (Builder $query) => $query->where('handoff', true)),
                Filter::make('unanswered')->label('سؤال بی‌جواب دارند')->query(fn (Builder $query) => $query->where('unanswered', true)),
            ])
            ->recordActions([ViewAction::make()->label('دیدن')]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('گفتگو')->columns(4)->columnSpanFull()->schema([
                TextEntry::make('customer.mobile')->label('مشتری')->placeholder('مهمان')
                    ->formatStateUsing(fn ($state, ChatConversation $c) => trim(($c->customer?->displayName() ?? '').' '.PersianText::toPersianDigits((string) $state))),
                TextEntry::make('mobile')->label('شماره برای تماس')->placeholder('—')->copyable()->formatStateUsing(fn ($state) => PersianText::toPersianDigits((string) $state)),
                TextEntry::make('page')->label('شروع از صفحه')->placeholder('—'),
                TextEntry::make('created_at')->label('تاریخ')->jalali(true),
                TextEntry::make('transcript')->hiddenLabel()->columnSpanFull()->state(fn (ChatConversation $c) => self::transcript($c))->html(),
            ]),
        ]);
    }

    private static function transcript(ChatConversation $conversation): HtmlString
    {
        $rows = $conversation->messages()->get()->map(function (ChatMessage $m) {
            $meta = (array) $m->meta;
            $tags = array_filter([
                ! empty($meta['ai']) ? '✨ هوش مصنوعی' : null,
                ! empty($meta['unanswered']) ? '❓ بی‌جواب' : null,
                ! empty($meta['products']) ? '🛍 '.PersianText::toPersianDigits((string) count($meta['products'])).' محصول' : null,
            ]);

            return '<div class="flex '.($m->role === 'user' ? 'justify-start' : 'justify-end').'"><div class="max-w-[80%] rounded-xl px-3 py-2 text-sm '
                .($m->role === 'user' ? 'bg-primary-50 dark:bg-primary-500/10' : 'bg-gray-100 dark:bg-white/5').'">'
                .'<div class="whitespace-pre-line">'.e((string) $m->text).'</div>'
                .($tags ? '<div class="mt-1 text-[11px] text-gray-500">'.e(implode(' · ', $tags)).'</div>' : '')
                .'<div class="mt-1 text-[11px] text-gray-400">'.e(PersianText::toPersianDigits($m->created_at?->copy()->setTimezone(config('khaneh.display_timezone', 'Asia/Tehran'))->format('H:i') ?? '')).'</div></div></div>';
        })->implode('');

        return new HtmlString('<div class="space-y-2">'.$rows.'</div>');
    }

    /** «ساخت جواب آماده» from the last question it couldn't answer */
    public static function faqAction(): Action
    {
        return Action::make('faq')->label('ساخت جواب آماده')->icon('heroicon-o-light-bulb')->color('primary')
            ->fillForm(function (ChatConversation $record) {
                $unanswered = $record->messages()->where('role', 'bot')->get()->last(fn (ChatMessage $m) => ! empty($m->meta['unanswered']));
                $question = $unanswered
                    ? $record->messages()->where('role', 'user')->where('id', '<', $unanswered->id)->reorder('id', 'desc')->value('text')
                    : $record->messages()->where('role', 'user')->reorder('id', 'desc')->value('text');

                return ['question' => $question, 'is_active' => true];
            })
            ->schema(ChatFaqResource::fields())
            ->action(function (array $data, ChatConversation $record) {
                ChatFaq::query()->create($data);
                $record->forceFill(['unanswered' => false])->save();
                Notification::make()->success()->title('جواب آماده ساخته شد؛ از این به بعد چت همین را می‌گوید')->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChatConversations::route('/'),
            'view' => ViewChatConversation::route('/{record}'),
        ];
    }
}
