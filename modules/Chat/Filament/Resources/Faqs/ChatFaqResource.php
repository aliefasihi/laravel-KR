<?php

/**
 * Khaneh Romantic — Admin: the site assistant's ready answers
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Chat\Filament\Resources\Faqs;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Modules\Chat\Filament\Resources\Faqs\Pages\ManageChatFaqs;
use Modules\Chat\Models\ChatFaq;
use UnitEnum;

class ChatFaqResource extends Resource
{
    protected static ?string $model = ChatFaq::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static string|UnitEnum|null $navigationGroup = 'ارتباط با مشتری';

    protected static ?int $navigationSort = 31;

    protected static ?string $navigationLabel = 'جواب‌های آماده‌ی چت';

    protected static ?string $modelLabel = 'جواب آماده';

    protected static ?string $pluralModelLabel = 'جواب‌های آماده‌ی چت';

    protected static ?string $slug = 'chat-answers';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components(self::fields());
    }

    /** @return list<mixed> also used by «ساخت جواب آماده» on a conversation */
    public static function fields(): array
    {
        return [
            TextInput::make('question')->label('سؤال (همان‌طور که مشتری‌ها می‌پرسند)')->required()->maxLength(250)
                ->placeholder('مثلاً: هزینه‌ی ارسال به شهرستان چقدر است؟'),
            Textarea::make('answer')->label('جواب')->required()->rows(4)->maxLength(1500),
            TagsInput::make('keywords')->label('کلمه‌های کلیدی (اختیاری)')->placeholder('مثلاً: شهرستان، ارسال')
                ->helperText('اگر یکی از این‌ها در پیام باشد، همین جواب داده می‌شود.'),
            TextInput::make('link')->label('لینک «بیشتر» (اختیاری)')->maxLength(250)->extraInputAttributes(['dir' => 'ltr'])->placeholder('/page/shipping'),
            Toggle::make('is_active')->label('فعال')->default(true),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('hits', 'desc')
            ->columns([
                TextColumn::make('question')->label('سؤال')->searchable()->weight('bold')->wrap()
                    ->description(fn (ChatFaq $f) => mb_substr($f->answer, 0, 120)),
                TextColumn::make('hits')->label('دفعات استفاده')->sortable()->faDigits(),
                ToggleColumn::make('is_active')->label('فعال'),
            ])
            ->recordActions([EditAction::make()->label(''), DeleteAction::make()->label('')])
            ->emptyStateHeading('هنوز جواب آماده‌ای نیست')
            ->emptyStateDescription('سؤال‌های پرتکرار مشتری‌ها را اینجا بنویسید؛ سؤال‌های بی‌جواب چت هم در «گفتگوهای چت» علامت می‌خورند.');
    }

    public static function getPages(): array
    {
        return ['index' => ManageChatFaqs::route('/')];
    }
}
