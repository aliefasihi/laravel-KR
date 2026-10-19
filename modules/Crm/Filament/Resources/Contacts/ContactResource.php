<?php

/**
 * Khaneh Romantic — Admin: contacts (everyone who sent a form, bought or signed up) — CRM base
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Crm\Filament\Resources\Contacts;

use App\Core\Support\Jalali;
use App\Core\Support\PersianText;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Crm\Filament\Resources\Contacts\Pages\ListContacts;
use Modules\Crm\Filament\Resources\Contacts\Pages\ViewContact;
use Modules\Crm\Filament\Resources\Contacts\RelationManagers\SubmissionsRelationManager;
use Modules\Crm\Models\Contact;
use Modules\Crm\Support\Csv;
use Modules\Crm\Support\Inbox;
use Modules\Customer\Filament\Resources\Customers\CustomerResource;
use Modules\Customer\Support\Mobile;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'ارتباط با مشتری';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'مخاطب';

    protected static ?string $pluralModelLabel = 'مخاطبان';

    protected static ?string $slug = 'contacts';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'mobile', 'email'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('name')->label('نام')->maxLength(120),
                TextInput::make('mobile')->label('موبایل')->extraInputAttributes(['dir' => 'ltr'])
                    ->dehydrateStateUsing(fn ($state) => Mobile::normalize($state) ?? ($state ?: null))
                    ->rule(fn () => fn ($attribute, $value, $fail) => $value && ! Mobile::normalize($value) ? $fail('شماره‌ی موبایل درست نیست.') : null)
                    ->unique(ignoreRecord: true),
                TextInput::make('email')->label('ایمیل')->email()->extraInputAttributes(['dir' => 'ltr'])->unique(ignoreRecord: true),
                TagsInput::make('tags')->label('برچسب‌ها')->placeholder('مثلاً: عمده‌فروش، VIP')
                    ->suggestions(fn () => self::tagOptions()),
                Toggle::make('newsletter_sms')->label('عضو خبرنامه‌ی پیامکی'),
                Toggle::make('newsletter_email')->label('عضو خبرنامه‌ی ایمیلی'),
                Textarea::make('notes')->label('یادداشت')->rows(3)->columnSpanFull(),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make()->columns(3)->schema([
                TextEntry::make('name')->label('نام')->placeholder('—'),
                TextEntry::make('mobile')->label('موبایل')->placeholder('—')->formatStateUsing(fn ($state) => PersianText::toPersianDigits($state))
                    ->url(fn (Contact $c) => $c->mobile ? 'tel:'.$c->mobile : null),
                TextEntry::make('email')->label('ایمیل')->placeholder('—'),
                TextEntry::make('customer.mobile')->label('حساب مشتری')->placeholder('ندارد')
                    ->formatStateUsing(fn (Contact $c) => $c->customer?->displayName())
                    ->url(fn (Contact $c) => $c->customer_id && class_exists(CustomerResource::class)
                        ? rescue(fn () => CustomerResource::getUrl('edit', ['record' => $c->customer_id]), null, false) : null),
                TextEntry::make('tags')->label('برچسب‌ها')->badge()->placeholder('—'),
                TextEntry::make('source')->label('اولین آشنایی')->formatStateUsing(fn ($state) => Inbox::formLabel((string) $state))->placeholder('—'),
                TextEntry::make('first_seen_at')->label('اولین بار')->formatStateUsing(fn ($state) => $state ? Jalali::format($state) : '—'),
                TextEntry::make('last_seen_at')->label('آخرین بار')->formatStateUsing(fn ($state) => $state ? Jalali::format($state) : '—'),
                TextEntry::make('utm')->label('کمپین ورود')->placeholder('—')
                    ->formatStateUsing(fn (Contact $c) => $c->utm ? collect($c->utm)->map(fn ($v, $k) => $k.'='.$v)->implode(' · ') : null),
                IconEntry::make('newsletter_sms')->label('خبرنامه‌ی پیامکی')->boolean(),
                IconEntry::make('newsletter_email')->label('خبرنامه‌ی ایمیلی')->boolean(),
                TextEntry::make('notes')->label('یادداشت')->placeholder('—')->columnSpanFull(),
            ]),
        ]);
    }

    /** @return array<string, string> */
    public static function tagOptions(): array
    {
        return Contact::query()->whereNotNull('tags')->pluck('tags')->flatten()->filter()->unique()->sort()->mapWithKeys(fn ($t) => [$t => $t])->all();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('last_seen_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('نام')->searchable(['name', 'mobile', 'email'])
                    ->state(fn (Contact $c) => $c->displayName())
                    ->description(fn (Contact $c) => $c->name && $c->mobile ? PersianText::toPersianDigits($c->mobile) : null),
                TextColumn::make('email')->label('ایمیل')->toggleable(),
                IconColumn::make('customer_id')->label('مشتری')->boolean()->state(fn (Contact $c) => (bool) $c->customer_id),
                TextColumn::make('tags')->label('برچسب')->badge()->toggleable(),
                TextColumn::make('source')->label('اولین آشنایی')->formatStateUsing(fn ($state) => Inbox::formLabel((string) $state))->toggleable(),
                TextColumn::make('submissions_count')->label('فرم‌ها')->numeric()->sortable()
                    ->formatStateUsing(fn ($state) => PersianText::toPersianDigits($state)),
                IconColumn::make('newsletter_sms')->label('خبرنامه')->boolean()
                    ->state(fn (Contact $c) => $c->newsletter_sms || $c->newsletter_email),
                TextColumn::make('last_seen_at')->label('آخرین بار')->sortable()->formatStateUsing(fn ($state) => $state ? Jalali::format($state) : '—'),
            ])
            ->filters([
                TernaryFilter::make('customer_id')->label('حساب مشتری')->nullable()->trueLabel('دارد')->falseLabel('ندارد'),
                TernaryFilter::make('newsletter')->label('خبرنامه')
                    ->queries(
                        true: fn (Builder $query) => $query->where(fn ($w) => $w->where('newsletter_sms', true)->orWhere('newsletter_email', true)),
                        false: fn (Builder $query) => $query->where('newsletter_sms', false)->where('newsletter_email', false),
                    ),
                SelectFilter::make('tag')->label('برچسب')->options(fn () => self::tagOptions())
                    ->query(fn (Builder $query, array $data) => $data['value'] ? $query->whereJsonContains('tags', $data['value']) : $query),
                SelectFilter::make('source')->label('اولین آشنایی')
                    ->options(fn () => Contact::query()->whereNotNull('source')->distinct()->pluck('source')->mapWithKeys(fn ($s) => [$s => Inbox::formLabel($s)])->all()),
            ])
            ->recordActions([ViewAction::make()->iconButton(), EditAction::make()->iconButton()])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('tag')->label('افزودن برچسب')->icon('heroicon-o-tag')
                        ->schema([TagsInput::make('tags')->label('برچسب‌ها')->suggestions(fn () => self::tagOptions())->required()])
                        ->action(fn (Collection $records, array $data) => $records->each(fn (Contact $c) => $c->update(['tags' => array_values(array_unique([...($c->tags ?? []), ...$data['tags']]))])))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('export')->label('خروجی CSV')->icon('heroicon-o-arrow-down-tray')
                        ->action(fn (Collection $records) => self::export($records)),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /** @param  iterable<Contact>  $contacts */
    public static function export(iterable $contacts): StreamedResponse
    {
        $rows = (function () use ($contacts) {
            foreach ($contacts as $c) {
                yield [$c->id, $c->name, $c->mobile, $c->email, $c->customer_id ? 'بله' : '', implode('، ', $c->tags ?? []),
                    Inbox::formLabel((string) $c->source), $c->submissions_count, $c->newsletter_sms ? 'بله' : '', $c->newsletter_email ? 'بله' : '',
                    $c->first_seen_at ? Jalali::format($c->first_seen_at) : '', $c->last_seen_at ? Jalali::format($c->last_seen_at) : '', $c->utm['utm_source'] ?? '', $c->notes];
            }
        })();

        return Csv::download('contacts-'.now()->format('Ymd-His').'.csv',
            ['شناسه', 'نام', 'موبایل', 'ایمیل', 'مشتری', 'برچسب‌ها', 'اولین آشنایی', 'تعداد فرم', 'خبرنامه پیامکی', 'خبرنامه ایمیلی', 'اولین بار', 'آخرین بار', 'منبع', 'یادداشت'],
            $rows);
    }

    public static function getRelations(): array
    {
        return [SubmissionsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContacts::route('/'),
            'view' => ViewContact::route('/{record}'),
        ];
    }
}
