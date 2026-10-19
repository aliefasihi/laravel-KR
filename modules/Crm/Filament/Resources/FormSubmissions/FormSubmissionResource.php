<?php

/**
 * Khaneh Romantic — Admin: forms inbox (a tab per form, its own columns, export per tab)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Crm\Filament\Resources\FormSubmissions;

use App\Core\Support\Jalali;
use App\Core\Support\PersianText;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Crm\Filament\Resources\Contacts\ContactResource;
use Modules\Crm\Filament\Resources\FormSubmissions\Pages\ListFormSubmissions;
use Modules\Crm\Models\FormSubmission;
use Modules\Crm\Support\Csv;
use Modules\Crm\Support\Forms;
use Modules\Crm\Support\Inbox;
use UnitEnum;

class FormSubmissionResource extends Resource
{
    protected static ?string $model = FormSubmission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static string|UnitEnum|null $navigationGroup = 'ارتباط با مشتری';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'صندوق فرم‌ها';

    protected static ?string $modelLabel = 'فرم دریافتی';

    protected static ?string $pluralModelLabel = 'صندوق فرم‌ها';

    protected static ?string $slug = 'inbox';

    public static function getNavigationBadge(): ?string
    {
        return ($n = Inbox::newCount()) ? PersianText::toPersianDigits($n) : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        $tab = $table->getLivewire()->activeTab ?? 'all';
        $form = $tab !== 'all' ? Forms::get($tab) : null;

        $columns = [
            TextColumn::make('created_at')->label('تاریخ')->sortable()
                ->formatStateUsing(fn ($state) => Jalali::format($state))
                ->description(fn (FormSubmission $s) => PersianText::toPersianDigits($s->created_at->copy()->setTimezone(config('khaneh.display_timezone'))->format('H:i'))),
            TextColumn::make('form')->label('فرم')->badge()->color('gray')->visible(! $form)
                ->formatStateUsing(fn (string $state) => Inbox::formLabel($state)),
            TextColumn::make('contact.name')->label('فرستنده')
                ->state(fn (FormSubmission $s) => $s->contact?->displayName() ?? '—')
                ->description(fn (FormSubmission $s) => ! $s->contact?->name ? null : ($s->contact->mobile ? PersianText::toPersianDigits($s->contact->mobile) : $s->contact->email))
                ->url(fn (FormSubmission $s) => $s->contact_id ? ContactResource::getUrl('view', ['record' => $s->contact_id]) : null)
                ->searchable(query: fn (Builder $query, string $search) => $query->whereHas('contact', fn ($c) => $c
                    ->where('name', 'like', "%{$search}%")->orWhere('mobile', 'like', '%'.PersianText::toLatinDigits($search).'%')->orWhere('email', 'like', "%{$search}%"))
                    ->orWhere('data', 'like', '%'.$search.'%')),
        ];
        if ($form) {
            foreach ($form->fields as $key => $label) {
                $columns[] = TextColumn::make('field_'.$key)->label($label)->wrap()->limit(120)
                    ->state(fn (FormSubmission $s) => is_scalar($v = $s->data[$key] ?? null) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE));
            }
        } else {
            $columns[] = TextColumn::make('summary')->label('خلاصه')->wrap()->state(fn (FormSubmission $s) => Inbox::summary($s));
        }
        $columns[] = TextColumn::make('status')->label('وضعیت')->badge()
            ->formatStateUsing(fn (string $state) => FormSubmission::STATUSES[$state] ?? $state)
            ->color(fn (string $state) => FormSubmission::COLORS[$state] ?? 'gray');
        $columns[] = TextColumn::make('utm.utm_source')->label('منبع')->toggleable(isToggledHiddenByDefault: true)
            ->state(fn (FormSubmission $s) => $s->utm['utm_source'] ?? null);
        $columns[] = TextColumn::make('page_url')->label('صفحه')->limit(40)->toggleable(isToggledHiddenByDefault: true);

        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('contact'))
            ->recordClasses(fn (FormSubmission $s) => $s->status === 'new' ? 'font-semibold' : null)
            ->columns($columns)
            ->filters([
                SelectFilter::make('status')->label('وضعیت')->options(FormSubmission::STATUSES),
                Filter::make('date')->label('تاریخ')
                    ->schema([DatePicker::make('from')->label('از'), DatePicker::make('until')->label('تا')])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                        ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))),
            ])
            ->recordAction('open')
            ->recordActions([
                self::openAction(),
                Action::make('done')->label('رسیدگی شد')->icon('heroicon-o-check')->color('success')->iconButton()
                    ->visible(fn (FormSubmission $s) => $s->status !== 'done')
                    ->action(fn (FormSubmission $s) => Inbox::setStatus($s, 'done')),
                DeleteAction::make()->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ...collect(['read' => 'heroicon-o-envelope-open', 'done' => 'heroicon-o-check', 'spam' => 'heroicon-o-no-symbol'])
                        ->map(fn ($icon, $status) => BulkAction::make('mark_'.$status)->label(FormSubmission::STATUSES[$status])->icon($icon)
                            ->action(fn (Collection $records) => $records->each(fn (FormSubmission $s) => Inbox::setStatus($s, $status)))
                            ->deselectRecordsAfterCompletion())->values()->all(),
                    BulkAction::make('export')->label('خروجی CSV')->icon('heroicon-o-arrow-down-tray')
                        ->action(function (Collection $records) use ($form) {
                            [$headers, $rows] = Inbox::export($records->load('contact'), $form?->key);

                            return Csv::download('inbox-'.($form?->key ?? 'all').'-'.now()->format('Ymd-His').'.csv', $headers, $rows);
                        }),
                    DeleteBulkAction::make()->after(fn () => Inbox::forgetBadge()),
                ]),
            ]);
    }

    public static function openAction(): Action
    {
        return Action::make('open')->label('مشاهده')->icon('heroicon-o-eye')->iconButton()->color('gray')
            ->modalHeading(fn (FormSubmission $s) => Inbox::formLabel($s->form).' — '.($s->contact?->displayName() ?? 'بازدیدکننده'))
            ->modalWidth('2xl')
            ->mountUsing(function (FormSubmission $s, $form = null) {
                if ($s->status === 'new') {
                    Inbox::setStatus($s, 'read');
                }
            })
            ->fillForm(fn (FormSubmission $s) => ['status' => $s->status, 'note' => $s->note])
            ->modalContent(fn (FormSubmission $s) => view('crm::filament.submission', [
                'submission' => $s->loadMissing('contact'),
                'definition' => Forms::get($s->form),
                'subjectUrl' => ($cb = Forms::get($s->form)?->subjectUrl) ? rescue(fn () => $cb($s), null, false) : null,
            ]))
            ->schema([
                Select::make('status')->label('وضعیت')->options(FormSubmission::STATUSES)->native(false)->required(),
                Textarea::make('note')->label('یادداشت داخلی')->rows(2),
            ])
            ->modalSubmitActionLabel('ذخیره')
            ->action(function (FormSubmission $s, array $data) {
                $s->note = $data['note'] ?: null;
                Inbox::setStatus($s, $data['status']);
            });
    }

    public static function getPages(): array
    {
        return ['index' => ListFormSubmissions::route('/')];
    }
}
