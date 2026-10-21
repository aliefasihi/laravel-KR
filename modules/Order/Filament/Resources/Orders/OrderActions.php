<?php

/**
 * Khaneh Romantic — Admin order actions (used on the orders table and the order page)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Order\Filament\Resources\Orders;

use App\Core\Modules\Modules;
use App\Core\Support\Money;
use App\Core\Support\PersianText;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Text;
use Illuminate\Support\Facades\URL;
use Modules\Order\Models\Order;
use Modules\Order\Support\AdminOrders;
use Modules\Order\Support\OrderException;
use Modules\Order\Support\OrderWorkflow;
use Modules\Payment\Support\PaymentReview;

final class OrderActions
{
    public static function changeStatus(): Action
    {
        return Action::make('changeStatus')->label('تغییر وضعیت')->icon('heroicon-o-arrow-path')
            ->fillForm(fn (Order $record) => ['status' => $record->status, 'notify' => true])
            ->schema([
                Select::make('status')->label('وضعیت تازه')->options(Order::STATUSES)->required()->native(false),
                Textarea::make('note')->label('توضیح (در تاریخچه)')->rows(2),
                Toggle::make('notify')->label('پیامک به مشتری (اگر برای این وضعیت الگو تعریف شده)'),
            ])
            ->action(function (Order $record, array $data) {
                app(OrderWorkflow::class)->changeStatus($record->load('items'), $data['status'], $data['note'] ?: null, auth()->id(), (bool) $data['notify']);
                Notification::make()->success()->title('وضعیت سفارش '.$record->number.': '.$record->statusLabel())->send();
            });
    }

    public static function ship(): Action
    {
        return Action::make('ship')->label('ارسال / کد رهگیری')->icon('heroicon-o-truck')->color('info')
            ->visible(fn (Order $record) => in_array($record->status, ['processing', 'shipped', 'on_hold'], true))
            ->fillForm(fn (Order $record) => ['tracking_code' => $record->tracking_code])
            ->schema([
                TextInput::make('tracking_code')->label('کد رهگیری مرسوله')->extraInputAttributes(['dir' => 'ltr'])
                    ->helperText('سفارش «ارسال شده» می‌شود و کد با پیامک برای مشتری می‌رود.'),
            ])
            ->action(function (Order $record, array $data) {
                app(OrderWorkflow::class)->ship($record->load('items'), PersianText::toLatinDigits((string) $data['tracking_code']) ?: null, auth()->id());
                Notification::make()->success()->title('سفارش ارسال شد')->send();
            });
    }

    public static function paymentReceived(): Action
    {
        return Action::make('paymentReceived')->label('ثبت دریافت وجه')->icon('heroicon-o-banknotes')->color('success')
            ->visible(fn (Order $record) => ! $record->isPaid() && Modules::enabled('Payment') && ! in_array($record->status, ['cancelled', 'refunded'], true))
            ->schema([
                TextInput::make('reference')->label('شماره‌ی پیگیری / توضیح')->placeholder('مثلاً رسید کارت‌خوان'),
            ])
            ->modalDescription(fn (Order $record) => 'مبلغ '.Money::toman($record->total).' تومان برای سفارش '.$record->number.' پرداخت‌شده ثبت می‌شود.')
            ->action(function (Order $record, array $data) {
                app(PaymentReview::class)->recordManual($record, $record->payment_method ?: 'manual', $data['reference'] ?: null, auth()->id());
                Notification::make()->success()->title('پرداخت ثبت شد')->send();
            });
    }

    public static function addNote(): Action
    {
        return Action::make('addNote')->label('یادداشت')->icon('heroicon-o-chat-bubble-left-ellipsis')->color('gray')
            ->schema([
                Radio::make('type')->label('نوع')->options(['private' => 'یادداشت داخلی (فقط پنل)', 'customer' => 'پیام به مشتری (در صفحه‌ی سفارشش)'])->default('private')->required(),
                Textarea::make('body')->label('متن')->required()->rows(3),
            ])
            ->action(function (Order $record, array $data) {
                app(OrderWorkflow::class)->addNote($record, $data['body'], $data['type'], auth()->id());
                Notification::make()->success()->title('یادداشت ثبت شد')->send();
            });
    }

    public static function refund(): Action
    {
        return Action::make('refund')->label('استرداد وجه')->icon('heroicon-o-receipt-refund')->color('danger')
            ->visible(fn (Order $record) => $record->isPaid() && $record->refunded_total < $record->total)
            ->fillForm(fn (Order $record) => [
                'amount' => intdiv($record->total - $record->refunded_total, 10),
                'items' => $record->items->mapWithKeys(fn ($i) => ['item_'.$i->id => 0])->all(),
            ])
            ->schema(fn (Order $record) => [
                TextInput::make('amount')->label('مبلغ استرداد')->numeric()->suffix('تومان')->required()
                    ->maxValue(intdiv($record->total - $record->refunded_total, 10)),
                Fieldset::make('برگشت به موجودی')->statePath('items')->schema(
                    $record->items->map(fn ($i) => TextInput::make('item_'.$i->id)->label($i->name)->numeric()->minValue(0)
                        ->maxValue($i->quantity - $i->refunded_quantity)->suffix('از '.PersianText::toPersianDigits($i->quantity - $i->refunded_quantity)))->all()
                )->columns(1),
                Textarea::make('reason')->label('دلیل')->rows(2),
                Toggle::make('to_wallet')->label('این مبلغ به کیف پول مشتری برگردد (به‌جای کارت بانکی)')
                    ->visible(fn () => Modules::enabled('Loyalty') && $record->customer_id),
            ])
            ->modalDescription('پول از پنل بانک یا درگاه برگردانده می‌شود (یا به کیف پول)؛ این‌جا ثبت و موجودی به‌روز می‌شود. سهم پرداخت‌شده از کیف پول خودکار برمی‌گردد.')
            ->action(function (Order $record, array $data) {
                $quantities = [];
                foreach ((array) ($data['items'] ?? []) as $key => $qty) {
                    $quantities[(int) str_replace('item_', '', $key)] = (int) $qty;
                }
                app(OrderWorkflow::class)->refund($record->load('items'), (int) $data['amount'] * 10, $quantities, $data['reason'] ?: null, auth()->id(), (bool) ($data['to_wallet'] ?? false));
                Notification::make()->success()->title('استرداد ثبت شد')->send();
            });
    }

    public static function editItems(): Action
    {
        return Action::make('editItems')->label('ویرایش کالاها')->icon('heroicon-o-pencil-square')->color('gray')
            ->visible(fn (Order $record) => ! in_array($record->status, ['cancelled', 'failed', 'refunded'], true) && $record->refunded_total === 0)
            ->modalWidth('5xl')
            ->fillForm(fn (Order $record) => [
                'items' => $record->items->map(fn ($i) => ['variant_id' => $i->variant_id, 'quantity' => $i->quantity, 'unit_price' => intdiv($i->unit_price, 10)])->all(),
                'shipping_total' => intdiv($record->shipping_total, 10),
                'discount' => intdiv($record->discount, 10),
            ])
            ->schema([
                OrderForm::items(),
                Grid::make(3)->schema([
                    TextInput::make('shipping_total')->label('هزینه‌ی ارسال')->numeric()->minValue(0)->suffix('تومان')->live(onBlur: true),
                    TextInput::make('discount')->label('تخفیف')->numeric()->minValue(0)->suffix('تومان')->live(onBlur: true),
                    Toggle::make('allow_backorder')->label('اجازه‌ی بیش از موجودی')->inline(false),
                ]),
                Text::make(fn ($get) => OrderForm::total((array) $get('items'), $get('shipping_total'), $get('discount')))->weight('bold'),
            ])
            ->modalDescription('موجودی انبار به‌اندازه‌ی تغییرها کم یا زیاد می‌شود و تغییر در تاریخچه ثبت می‌شود.')
            ->action(function (Order $record, array $data, Action $action) {
                try {
                    app(AdminOrders::class)->updateItems($record, OrderForm::itemsToRials($data['items'] ?? []),
                        (int) $data['shipping_total'] * 10, (int) $data['discount'] * 10, (bool) ($data['allow_backorder'] ?? false), auth()->id());
                    Notification::make()->success()->title('کالاهای سفارش به‌روز شد')->send();
                } catch (OrderException $e) {
                    Notification::make()->danger()->title($e->getMessage())->send();
                    $action->halt();
                }
            });
    }

    public static function editAddress(): Action
    {
        $provinces = config('modules.customer.provinces');

        return Action::make('editAddress')->label('ویرایش نشانی')->icon('heroicon-o-map-pin')->color('gray')
            ->fillForm(fn (Order $record) => $record->only(['recipient_name', 'recipient_mobile', 'province', 'city', 'address', 'plaque', 'unit', 'postal_code']))
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('recipient_name')->label('گیرنده')->required(),
                    TextInput::make('recipient_mobile')->label('موبایل')->required()->extraInputAttributes(['dir' => 'ltr']),
                    Select::make('province')->label('استان')->options(array_combine($provinces, $provinces))->required()->searchable(),
                    TextInput::make('city')->label('شهر')->required(),
                    Textarea::make('address')->label('نشانی')->required()->columnSpanFull(),
                    TextInput::make('plaque')->label('پلاک'),
                    TextInput::make('unit')->label('واحد'),
                    TextInput::make('postal_code')->label('کد پستی')->extraInputAttributes(['dir' => 'ltr']),
                ]),
            ])
            ->action(function (Order $record, array $data) {
                $record->update($data);
                $record->note('نشانی تحویل ویرایش شد.', 'system', auth()->id());
            });
    }

    public static function invoice(): Action
    {
        return Action::make('invoice')->label('فاکتور')->icon('heroicon-o-printer')->color('gray')
            ->url(fn (Order $record) => self::printUrl([$record->id]), shouldOpenInNewTab: true);
    }

    public static function packingSlip(): Action
    {
        return Action::make('slip')->label('برگه‌ی بسته‌بندی')->icon('heroicon-o-clipboard-document-list')->color('gray')
            ->url(fn (Order $record) => self::printUrl([$record->id], 'slip'), shouldOpenInNewTab: true);
    }

    public static function shippingLabel(): Action
    {
        return Action::make('label')->label('برچسب پستی')->icon('heroicon-o-tag')->color('gray')
            ->url(fn (Order $record) => self::printUrl([$record->id], 'label'), shouldOpenInNewTab: true);
    }

    /**
     * Signed, short-lived link: invoices open in a new tab without the panel, ready to print.
     *
     * @param  'invoice'|'slip'|'label'  $kind
     */
    public static function printUrl(array $ids, string $kind = 'invoice'): string
    {
        return URL::temporarySignedRoute('order.print', now()->addMinutes(30), ['ids' => implode(',', $ids), 'kind' => $kind]);
    }
}
