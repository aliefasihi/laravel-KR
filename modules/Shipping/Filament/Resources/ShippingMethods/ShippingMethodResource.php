<?php

/**
 * Khaneh Romantic — Admin: shipping methods
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Shipping\Filament\Resources\ShippingMethods;

use App\Core\Support\Money;
use App\Filament\Forms\TomanInput;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Modules\Shipping\Filament\Resources\ShippingMethods\Pages\CreateShippingMethod;
use Modules\Shipping\Filament\Resources\ShippingMethods\Pages\EditShippingMethod;
use Modules\Shipping\Filament\Resources\ShippingMethods\Pages\ListShippingMethods;
use Modules\Shipping\Models\ShippingMethod;
use UnitEnum;

class ShippingMethodResource extends Resource
{
    protected static ?string $model = ShippingMethod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|UnitEnum|null $navigationGroup = 'فروش';

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'روش ارسال';

    protected static ?string $pluralModelLabel = 'روش‌های ارسال';

    protected static ?string $slug = 'shipping-methods';

    public static function provinces(): array
    {
        $list = config('modules.customer.provinces') ?: ['تهران'];

        return array_combine($list, $list);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Group::make([
                Section::make('روش ارسال')->schema([
                    TextInput::make('name')->label('نام')->required()->placeholder('پست پیشتاز'),
                    TextInput::make('code')->label('شناسه (انگلیسی)')->required()->unique(ignoreRecord: true)->alphaDash()
                        ->extraInputAttributes(['dir' => 'ltr'])->placeholder('post'),
                    TextInput::make('description')->label('توضیح برای مشتری')->placeholder('۳ تا ۵ روز کاری')->columnSpanFull(),
                    Toggle::make('pay_on_delivery')->label('پس‌کرایه (هزینه را مأمور هنگام تحویل می‌گیرد)')->live()->columnSpanFull(),
                ])->columns(2),
                Section::make('هزینه')->hidden(fn (Get $get) => $get('pay_on_delivery'))->schema([
                    TomanInput::make('cost')->label('هزینه‌ی پایه')->required()->default(0),
                    TomanInput::make('cost_per_extra_item')->label('به ازای هر کالای اضافه')->default(0),
                    TomanInput::make('free_over')->label('رایگان برای خرید بالای')->helperText('خالی = همیشه هزینه دارد'),
                    Repeater::make('province_cost_rows')->label('هزینه‌ی جدا برای بعضی استان‌ها')
                        ->schema([
                            Select::make('province')->label('استان')->options(self::provinces())->required()->searchable(),
                            TextInput::make('cost')->label('هزینه')->numeric()->minValue(0)->suffix('تومان')->required(),
                        ])->columns(2)->defaultItems(0)->addActionLabel('افزودن استان')->columnSpanFull()
                        ->afterStateHydrated(fn (Repeater $component, ?ShippingMethod $record) => $component->state(
                            collect($record?->province_costs ?? [])->map(fn ($cost, $province) => ['province' => $province, 'cost' => intdiv((int) $cost, 10)])->values()->all()
                        ))
                        ->dehydrated(false),
                ])->columns(3),
            ])->columnSpan(['lg' => 2]),
            Group::make([
                Section::make('کجا و کِی')->schema([
                    Toggle::make('is_active')->label('فعال')->default(true),
                    Select::make('provinces')->label('فقط این استان‌ها')->multiple()->options(self::provinces())->searchable()
                        ->helperText('خالی = همه‌ی استان‌ها'),
                    TagsInput::make('cities')->label('فقط این شهرها')->placeholder('تهران')->helperText('مثلاً برای پیک'),
                    TomanInput::make('min_subtotal')->label('حداقل مبلغ سبد'),
                    TomanInput::make('max_subtotal')->label('حداکثر مبلغ سبد'),
                    TextInput::make('sort')->label('ترتیب')->numeric()->default(0),
                ]),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    /** Repeater rows (Toman) → {province: cost in Rials}. */
    public static function provinceCosts(array $rows): ?array
    {
        $costs = [];
        foreach ($rows as $row) {
            if (! empty($row['province']) && $row['cost'] !== null && $row['cost'] !== '') {
                $costs[$row['province']] = (int) $row['cost'] * 10;
            }
        }

        return $costs ?: null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('name')->label('نام')->description(fn (ShippingMethod $r) => $r->description),
                TextColumn::make('cost')->label('هزینه')->state(fn (ShippingMethod $r) => $r->pay_on_delivery ? 'پس‌کرایه' : Money::toman($r->cost).' تومان'
                    .($r->province_costs ? ' (+'.count($r->province_costs).' استان جدا)' : '')),
                TextColumn::make('free_over')->label('رایگان از')->formatStateUsing(fn ($state) => $state ? Money::toman((int) $state).' تومان' : null)->placeholder('—'),
                TextColumn::make('area')->label('محدوده')->state(fn (ShippingMethod $r) => $r->cities ? implode('، ', $r->cities) : ($r->provinces ? implode('، ', $r->provinces) : 'سراسر ایران'))->wrap(),
                ToggleColumn::make('is_active')->label('فعال'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShippingMethods::route('/'),
            'create' => CreateShippingMethod::route('/create'),
            'edit' => EditShippingMethod::route('/{record}/edit'),
        ];
    }
}
