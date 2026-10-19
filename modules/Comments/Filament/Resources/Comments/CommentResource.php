<?php

/**
 * Khaneh Romantic — Admin: blog comments (approve, reply as the shop, spam)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Comments\Filament\Resources\Comments;

use App\Core\Support\Jalali;
use App\Core\Support\PersianText;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Comments\Filament\Resources\Comments\Pages\ListComments;
use Modules\Comments\Models\Comment;
use UnitEnum;

class CommentResource extends Resource
{
    protected static ?string $model = Comment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'محتوا';

    protected static ?int $navigationSort = 12;

    protected static ?string $modelLabel = 'نظر';

    protected static ?string $pluralModelLabel = 'نظرات مقاله‌ها';

    protected static ?string $slug = 'comments';

    public static function getNavigationBadge(): ?string
    {
        $n = (int) rescue(fn () => Cache::remember(Comment::BADGE_KEY, 60, fn () => Comment::query()->where('status', 'pending')->count()), 0, report: false);

        return $n ? PersianText::toPersianDigits($n) : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('نام')->required()->maxLength(80),
            Textarea::make('body')->label('متن')->rows(5)->required(),
        ])->columns(1);
    }

    public static function reply(Comment $comment, string $body): Comment
    {
        $root = $comment->parent_id ? $comment->parent : $comment;
        $comment->status === 'approved' || $comment->update(['status' => 'approved']);

        return Comment::create([
            'commentable_type' => $root->commentable_type,
            'commentable_id' => $root->commentable_id,
            'parent_id' => $root->id,
            'user_id' => auth()->id(),
            'name' => auth()->user()?->name ?: 'خانه رمانتیک',
            'body' => trim($body),
            'status' => 'approved',
            'is_staff' => true,
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['commentable', 'parent']))
            ->columns([
                TextColumn::make('body')->label('نظر')->wrap()->limit(180)->searchable()
                    ->description(fn (Comment $c) => $c->parent ? 'در پاسخ به '.($c->parent->is_staff ? 'خانه رمانتیک' : $c->parent->name) : null),
                TextColumn::make('commentable.title')->label('مقاله')->limit(40)
                    ->url(fn (Comment $c) => $c->commentable && method_exists($c->commentable, 'url') ? $c->commentable->url().'#comments' : null, shouldOpenInNewTab: true),
                TextColumn::make('name')->label('نویسنده')->searchable(['name', 'mobile', 'email'])
                    ->state(fn (Comment $c) => $c->is_staff ? 'خانه رمانتیک (پاسخ)' : $c->name)
                    ->description(fn (Comment $c) => $c->mobile ? PersianText::toPersianDigits($c->mobile) : $c->email),
                TextColumn::make('status')->label('وضعیت')->badge()
                    ->formatStateUsing(fn (string $state) => Comment::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => Comment::COLORS[$state] ?? 'gray'),
                TextColumn::make('likes')->label('پسند')->formatStateUsing(fn ($state) => PersianText::toPersianDigits($state))->toggleable(),
                TextColumn::make('created_at')->label('تاریخ')->sortable()->formatStateUsing(fn ($state) => Jalali::format($state)),
            ])
            ->filters([
                SelectFilter::make('status')->label('وضعیت')->options(Comment::STATUSES),
            ])
            ->recordActions([
                Action::make('approve')->label('تأیید')->icon('heroicon-o-check')->color('success')->iconButton()
                    ->visible(fn (Comment $c) => $c->status !== 'approved')
                    ->action(fn (Comment $c) => $c->update(['status' => 'approved'])),
                Action::make('reply')->label('پاسخ')->icon('heroicon-o-chat-bubble-left')->iconButton()
                    ->visible(fn (Comment $c) => ! $c->is_staff)
                    ->modalDescription(fn (Comment $c) => '«'.Str::limit($c->body, 200).'»')
                    ->schema([Textarea::make('body')->label('پاسخ خانه رمانتیک (نظر هم تأیید می‌شود)')->rows(4)->required()])
                    ->action(fn (Comment $c, array $data) => self::reply($c, $data['body'])),
                Action::make('reject')->label('رد')->icon('heroicon-o-x-mark')->color('gray')->iconButton()
                    ->visible(fn (Comment $c) => $c->status === 'pending' || $c->status === 'approved')
                    ->action(fn (Comment $c) => $c->update(['status' => 'rejected'])),
                EditAction::make()->iconButton(),
                DeleteAction::make()->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ...collect(['approved' => ['تأیید', 'heroicon-o-check'], 'rejected' => ['رد', 'heroicon-o-x-mark'], 'spam' => ['هرزنامه', 'heroicon-o-no-symbol']])
                        ->map(fn ($meta, $status) => BulkAction::make($status)->label($meta[0])->icon($meta[1])
                            ->action(fn (Collection $records) => $records->each(fn (Comment $c) => $c->update(['status' => $status])))
                            ->deselectRecordsAfterCompletion())->values()->all(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListComments::route('/')];
    }
}
