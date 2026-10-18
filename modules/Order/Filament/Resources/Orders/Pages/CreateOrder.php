<?php

/**
 * Khaneh Romantic — Admin: new order by hand (phone, in-store, Instagram…)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Order\Filament\Resources\Orders\Pages;

use App\Core\Support\PersianText;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Modules\Customer\Models\Customer;
use Modules\Customer\Support\Mobile;
use Modules\Order\Filament\Resources\Orders\OrderForm;
use Modules\Order\Filament\Resources\Orders\OrderResource;
use Modules\Order\Models\Order;
use Modules\Order\Support\AdminOrders;
use Modules\Order\Support\OrderException;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected static ?string $title = 'ثبت سفارش دستی';

    protected static bool $canCreateAnother = false;

    public function form(Schema $schema): Schema
    {
        $provinces = config('modules.customer.provinces');

        return $schema->components([
            Group::make([
                Section::make('مشتری')->schema([
                    Select::make('customer_id')->label('مشتری')->required()->searchable()->live()
                        ->getSearchResultsUsing(fn (string $search) => Customer::query()
                            ->where(fn ($q) => $q->where('mobile', 'like', '%'.PersianText::toLatinDigits($search).'%')
                                ->orWhere('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"))
                            ->limit(20)->get()->mapWithKeys(fn (Customer $c) => [$c->id => $c->displayName().' — '.$c->mobile])->all())
                        ->getOptionLabelUsing(fn ($value) => ($c = Customer::find($value)) ? $c->displayName().' — '.$c->mobile : null)
                        ->createOptionForm([
                            TextInput::make('mobile')->label('موبایل')->required()->extraInputAttributes(['dir' => 'ltr']),
                            TextInput::make('first_name')->label('نام'),
                            TextInput::make('last_name')->label('نام خانوادگی'),
                        ])
                        ->createOptionUsing(function (array $data) {
                            $mobile = Mobile::normalize($data['mobile']) ?? throw new \InvalidArgumentException('شماره‌ی موبایل درست نیست.');

                            return Customer::query()->firstOrCreate(['mobile' => $mobile], array_filter(['first_name' => $data['first_name'] ?? null, 'last_name' => $data['last_name'] ?? null]))->id;
                        })
                        ->afterStateUpdated(function ($state, Set $set) {
                            $customer = $state ? Customer::find($state) : null;
                            $address = $customer?->defaultAddress();
                            $set('address', [
                                'recipient_name' => $address?->recipient_name ?? $customer?->fullName(),
                                'recipient_mobile' => $address?->recipient_mobile ?? $customer?->mobile,
                                'province' => $address?->province ?? 'تهران',
                                'city' => $address?->city, 'address' => $address?->address, 'plaque' => $address?->plaque,
                                'unit' => $address?->unit, 'postal_code' => $address?->postal_code,
                            ]);
                        }),
                    Select::make('source')->label('کانال فروش')->options(AdminOrders::SOURCES)->default('phone')->required()->native(false),
                ])->columns(2),
                Section::make('کالاها')->schema([
                    OrderForm::items(),
                    Toggle::make('allow_backorder')->label('ثبت حتی اگر موجودی کافی نیست (پیش‌فروش)'),
                ]),
                Section::make('نشانی تحویل')->statePath('address')->collapsible()->schema([
                    TextInput::make('recipient_name')->label('گیرنده'),
                    TextInput::make('recipient_mobile')->label('موبایل گیرنده')->extraInputAttributes(['dir' => 'ltr']),
                    Select::make('province')->label('استان')->options(array_combine($provinces, $provinces))->default('تهران')->searchable(),
                    TextInput::make('city')->label('شهر'),
                    Textarea::make('address')->label('نشانی')->rows(2)->columnSpanFull(),
                    TextInput::make('plaque')->label('پلاک'),
                    TextInput::make('unit')->label('واحد'),
                    TextInput::make('postal_code')->label('کد پستی')->extraInputAttributes(['dir' => 'ltr']),
                ])->columns(2),
            ])->columnSpan(['lg' => 2]),
            Group::make([
                Section::make('ارسال و پرداخت')->schema([
                    Select::make('shipping_method')->label('روش ارسال')->options(OrderForm::shippingOptions())->native(false),
                    TextInput::make('shipping_total')->label('هزینه‌ی ارسال')->numeric()->minValue(0)->default(0)->suffix('تومان')->live(onBlur: true),
                    TextInput::make('discount')->label('تخفیف')->numeric()->minValue(0)->default(0)->suffix('تومان')->live(onBlur: true),
                    TextInput::make('coupon_code')->label('کد تخفیف (برای ثبت در گزارش)')->extraInputAttributes(['dir' => 'ltr']),
                    Select::make('payment_method')->label('روش پرداخت')->options(OrderForm::paymentOptions())->default('cash')->required()->native(false),
                    Toggle::make('paid')->label('پرداخت شده است')->default(true),
                    Select::make('status')->label('وضعیت')->options(collect(Order::STATUSES)->only(['pending', 'on_hold', 'processing', 'shipped', 'completed'])->all())
                        ->default('processing')->required()->native(false),
                    Toggle::make('reduce_stock')->label('از موجودی کم شود')->default(true),
                    Textarea::make('note')->label('توضیح')->rows(2),
                    Text::make(fn (Get $get) => OrderForm::total((array) $get('items'), $get('shipping_total'), $get('discount')))->weight('bold'),
                ]),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $shipping = OrderForm::shippingOptions();
        $payments = OrderForm::paymentOptions();

        try {
            return app(AdminOrders::class)->create([
                'customer_id' => (int) $data['customer_id'],
                'items' => OrderForm::itemsToRials($data['items'] ?? []),
                'address' => $data['address'] ?? [],
                'shipping_method' => $data['shipping_method'] ?? null,
                'shipping_title' => $shipping[$data['shipping_method'] ?? ''] ?? null,
                'shipping_total' => (int) ($data['shipping_total'] ?? 0) * 10,
                'discount' => (int) ($data['discount'] ?? 0) * 10,
                'coupon_code' => $data['coupon_code'] ?? null,
                'payment_method' => $data['payment_method'],
                'payment_title' => $payments[$data['payment_method']] ?? null,
                'paid' => (bool) ($data['paid'] ?? false),
                'status' => $data['status'],
                'source' => $data['source'],
                'note' => $data['note'] ?? null,
                'reduce_stock' => (bool) ($data['reduce_stock'] ?? true),
                'allow_backorder' => (bool) ($data['allow_backorder'] ?? false),
            ], auth()->id());
        } catch (OrderException $e) {
            Notification::make()->danger()->title($e->getMessage())->send();
            $this->halt();
        }
    }

    public function getBreadcrumb(): string
    {
        return 'ثبت دستی';
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('ثبت سفارش');
    }

    protected function getRedirectUrl(): string
    {
        return OrderResource::getUrl('view', ['record' => $this->record]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'سفارش '.$this->record->number.' ثبت شد';
    }
}
