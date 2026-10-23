<?php

/**
 * Khaneh Romantic — Admin: product questions — answer (publishes it and SMSes the asker), reject
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Questions\Filament\Resources\Questions;

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
use Filament\Schemas\Components\Text;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Modules\Questions\Filament\Resources\Questions\Pages\ListQuestions;
use Modules\Questions\Models\Question;
use Modules\Questions\Support\Answers;
use UnitEnum;

class QuestionResource extends Resource
{
    protected static ?string $model = Question::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static string|UnitEnum|null $navigationGroup = 'فروش';

    protected static ?int $navigationSort = 26;

    protected static ?string $modelLabel = 'پرسش';

    protected static ?string $pluralModelLabel = 'پرسش و پاسخ';

    protected static ?string $slug = 'questions';

    public static function getNavigationBadge(): ?string
    {
        return NavigationBadge::count(Question::BADGE_KEY, fn () => Question::query()->where('status', 'pending')->count());
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('product'))
            ->columns([
                TextColumn::make('body')->label('پرسش')->wrap()->limit(160)->searchable()
                    ->description(fn (Question $q) => $q->answer ? 'پاسخ: '.Str::limit($q->answer, 120) : null),
                TextColumn::make('product.name')->label('محصول')->limit(40)->searchable()
                    ->url(fn (Question $q) => $q->product?->url().'#questions', shouldOpenInNewTab: true),
                TextColumn::make('name')->label('پرسنده')->searchable(['name', 'mobile'])
                    ->description(fn (Question $q) => $q->mobile ? PersianText::toPersianDigits($q->mobile).($q->notify ? ' · پیامک' : '') : null),
                TextColumn::make('status')->label('وضعیت')->badge()->labels(Question::STATUSES, Question::COLORS),
                TextColumn::make('helpful')->label('مفید')->faDigits()->sortable()->toggleable(),
                TextColumn::make('created_at')->label('تاریخ')->sortable()->jalali(),
            ])
            ->filters([
                SelectFilter::make('status')->label('وضعیت')->options(Question::STATUSES)->default('pending'),
            ])
            ->recordActions([
                Action::make('answer')->label(fn (Question $q) => $q->answer ? 'ویرایش پاسخ' : 'پاسخ')->icon('heroicon-o-chat-bubble-left')->color('primary')
                    ->modalHeading(fn (Question $q) => 'پاسخ به: '.Str::limit($q->body, 80))
                    ->fillForm(fn (Question $q) => ['answer' => $q->answer])
                    ->schema(fn (Question $q) => [
                        Text::make($q->product?->name ?? '')->color('gray'),
                        Textarea::make('answer')->label('پاسخ فروشگاه (زیر محصول برای همه نمایش داده می‌شود)')->rows(5)->required()->maxLength(2000),
                        Text::make($q->notify && $q->mobile && ! $q->answer ? 'پرسنده با پیامک خبردار می‌شود.' : '')->color('gray'),
                    ])
                    ->action(fn (Question $q, array $data) => app(Answers::class)->answer($q, $data['answer'], auth()->id())),
                Action::make('reject')->label('رد')->icon('heroicon-o-x-mark')->color('gray')
                    ->visible(fn (Question $q) => $q->status !== 'rejected')
                    ->action(fn (Question $q) => $q->update(['status' => 'rejected'])),
                DeleteAction::make()->label(''),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('reject')->label('رد')->icon('heroicon-o-x-mark')
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'rejected']))->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListQuestions::route('/')];
    }
}
