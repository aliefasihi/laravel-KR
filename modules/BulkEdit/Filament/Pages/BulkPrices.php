<?php

/**
 * Khaneh Romantic — Admin: «ویرایش گروهی قیمت» — filter, preview, apply, undo
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\BulkEdit\Filament\Pages;

use App\Core\Support\PersianText;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Modules\BulkEdit\Models\PriceChange;
use Modules\BulkEdit\Support\PriceChanger;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use UnitEnum;

class BulkPrices extends Page
{
    protected string $view = 'bulkedit::filament.bulk-prices';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static string|UnitEnum|null $navigationGroup = 'کاتالوگ';

    protected static ?int $navigationSort = 12;

    protected static ?string $navigationLabel = 'ویرایش گروهی قیمت';

    protected static ?string $title = 'ویرایش گروهی قیمت';

    protected static ?string $slug = 'bulk-prices';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'status' => 'all', 'stock' => 'all', 'sale' => 'all',
            'field' => 'price', 'direction' => 'up', 'mode' => 'percent', 'rounding' => 10000,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make('کدام محصولات؟')->description('خالی = همه. دسته‌ها زیردسته‌هایشان را هم شامل می‌شوند.')
                ->icon('heroicon-o-funnel')->columns(3)->schema([
                    Select::make('categories')->label('دسته‌بندی')->multiple()->searchable()->live()
                        ->options(fn () => Category::query()->orderBy('name')->pluck('name', 'id')),
                    Select::make('brands')->label('برند')->multiple()->searchable()->live()
                        ->options(fn () => Brand::query()->orderBy('name_fa')->pluck('name_fa', 'id')),
                    TextInput::make('search')->label('نام یا کد کالا')->live(debounce: 600),
                    Select::make('status')->label('وضعیت محصول')->native(false)->live()
                        ->options(['all' => 'همه', 'active' => 'منتشرشده', 'draft' => 'پیش‌نویس']),
                    Select::make('stock')->label('موجودی')->native(false)->live()
                        ->options(['all' => 'همه', 'in' => 'موجود', 'out' => 'ناموجود']),
                    Select::make('sale')->label('حراج')->native(false)->live()
                        ->options(['all' => 'همه', 'yes' => 'فقط حراجی‌ها', 'no' => 'فقط بدون حراج']),
                    TextInput::make('min')->label('قیمت از (تومان)')->numeric()->live(debounce: 600),
                    TextInput::make('max')->label('قیمت تا (تومان)')->numeric()->live(debounce: 600),
                ]),
            Section::make('چه تغییری؟')->icon('heroicon-o-adjustments-vertical')->schema([
                Grid::make(3)->schema([
                    Radio::make('field')->label('روی')->options(PriceChanger::FIELDS)->live(),
                    ToggleButtons::make('direction')->label('جهت')->inline()->live()
                        ->options(['up' => 'افزایش', 'down' => 'کاهش'])->colors(['up' => 'danger', 'down' => 'success'])
                        ->icons(['up' => 'heroicon-o-arrow-trending-up', 'down' => 'heroicon-o-arrow-trending-down']),
                    ToggleButtons::make('mode')->label('به‌صورت')->inline()->live()->options(['percent' => 'درصد', 'amount' => 'مبلغ (تومان)']),
                ]),
                Grid::make(3)->schema([
                    TextInput::make('value')->label(fn (Get $get) => $get('mode') === 'amount' ? 'چند تومان؟' : 'چند درصد؟')
                        ->numeric()->minValue(0)->live(debounce: 500)
                        ->suffix(fn (Get $get) => $get('mode') === 'amount' ? 'تومان' : '٪'),
                    Select::make('rounding')->label('رُند کردن به نزدیک‌ترین')->native(false)->live()->options(PriceChanger::ROUNDING),
                ]),
            ]),
        ]);
    }

    /** @return array{models: int, products: int, rows: Collection, removed_sales: int} */
    public function preview(): array
    {
        $key = md5(json_encode($this->data));

        return $this->previews[$key] ??= app(PriceChanger::class)->preview($this->filters(), $this->operation());
    }

    /** @var array<string, array> one preview per state within a request (render + action checks) */
    private array $previews = [];

    public function history(): Collection
    {
        return PriceChange::query()->with('user')->latest('id')->limit(15)->get();
    }

    public function applyAction(): Action
    {
        return Action::make('apply')->label('اعمال تغییر')->icon('heroicon-o-check')->color('danger')
            ->requiresConfirmation()
            ->modalHeading('اعمال روی قیمت‌ها؟')
            ->modalDescription(fn () => 'قیمت '.PersianText::toPersianDigits($this->preview()['models']).' مدل عوض می‌شود. از قسمت «تغییرهای اخیر» می‌شود برگرداند.')
            ->disabled(fn () => $this->preview()['models'] === 0)
            ->action(function () {
                $change = app(PriceChanger::class)->apply($this->filters(), $this->operation(), auth()->id());
                Notification::make()->success()
                    ->title($change ? 'قیمت '.PersianText::toPersianDigits($change->variants_count).' مدل عوض شد' : 'چیزی عوض نشد')
                    ->send();
            });
    }

    public function undoAction(): Action
    {
        return Action::make('undo')->label('برگرداندن')->icon('heroicon-o-arrow-uturn-right')->color('gray')->size('sm')
            ->requiresConfirmation()
            ->modalDescription('قیمت‌ها به قبل از این تغییر برمی‌گردند. مدل‌هایی که بعدش دستی عوض شده‌اند دست نمی‌خورند.')
            ->action(function (array $arguments) {
                $change = PriceChange::query()->findOrFail($arguments['change']);
                $restored = app(PriceChanger::class)->undo($change);
                Notification::make()->success()->title(PersianText::toPersianDigits($restored).' مدل به قیمت قبل برگشت')->send();
            });
    }

    private function filters(): array
    {
        return $this->data ?? [];
    }

    private function operation(): array
    {
        return array_intersect_key($this->data ?? [], array_flip(['field', 'direction', 'mode', 'value', 'rounding']));
    }
}
