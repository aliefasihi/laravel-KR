<?php

/**
 * Khaneh Romantic — Form pieces for manual orders / editing items (admin)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Order\Filament\Resources\Orders;

use App\Core\Modules\Modules;
use App\Core\Support\Money;
use App\Core\Support\PersianText;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Modules\Catalog\Models\ProductVariant;
use Modules\Order\Payments\PaymentMethods;
use Modules\Shipping\Models\ShippingMethod;

final class OrderForm
{
    public static function variantLabel(ProductVariant $v): string
    {
        $v->loadMissing('product');

        return $v->product->name.($v->options ? ' ('.implode('، ', $v->options).')' : '')
            .' — '.$v->sku.' — '.Money::toman($v->currentPrice()).' تومان — موجودی '.PersianText::toPersianDigits($v->stock);
    }

    public static function items(): Repeater
    {
        return Repeater::make('items')->label('کالاها')
            ->schema([
                Select::make('variant_id')->label('کالا')->required()->searchable()->columnSpan(6)
                    ->getSearchResultsUsing(fn (string $search) => ProductVariant::query()->with('product')
                        ->where(fn ($q) => $q->where('sku', 'like', "%{$search}%")
                            ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%")))
                        ->limit(30)->get()->mapWithKeys(fn (ProductVariant $v) => [$v->id => self::variantLabel($v)])->all())
                    ->getOptionLabelUsing(fn ($value) => ($v = ProductVariant::find($value)) ? self::variantLabel($v) : null)
                    ->live(),
                TextInput::make('quantity')->label('تعداد')->numeric()->minValue(1)->default(1)->required()->columnSpan(2)->live(onBlur: true),
                TextInput::make('unit_price')->label('قیمت واحد (تومان)')->numeric()->minValue(0)->columnSpan(4)->live(onBlur: true)
                    ->placeholder(fn ($get) => ($v = ProductVariant::find($get('variant_id'))) ? Money::toman($v->currentPrice()).' (قیمت فعلی)' : 'قیمت فعلی سایت')
                    ->helperText('خالی = قیمت فعلی سایت'),
            ])
            ->columns(12)->minItems(1)->defaultItems(1)->addActionLabel('افزودن کالا')->reorderable(false);
    }

    /** Repeater rows (Toman) → AdminOrders items (Rials). */
    public static function itemsToRials(array $rows): array
    {
        return array_values(array_map(fn ($row) => [
            'variant_id' => (int) ($row['variant_id'] ?? 0),
            'quantity' => (int) ($row['quantity'] ?? 0),
            'unit_price' => ($row['unit_price'] ?? '') === '' || $row['unit_price'] === null ? null : (int) PersianText::toLatinDigits((string) $row['unit_price']) * 10,
        ], $rows));
    }

    /** Live total for the form footer. */
    public static function total(array $rows, $shipping, $discount): string
    {
        $sum = 0;
        foreach ($rows as $row) {
            $v = ! empty($row['variant_id']) ? ProductVariant::find($row['variant_id']) : null;
            if (! $v) {
                continue;
            }
            $unit = ($row['unit_price'] ?? '') !== '' && $row['unit_price'] !== null ? (int) $row['unit_price'] * 10 : $v->currentPrice();
            $sum += $unit * max(0, (int) ($row['quantity'] ?? 0));
        }
        $total = max(0, $sum - (int) $discount * 10) + (int) $shipping * 10;

        return 'جمع کالاها '.Money::toman($sum).' تومان · مبلغ کل '.Money::toman($total).' تومان';
    }

    /** @return array<string, string> */
    public static function shippingOptions(): array
    {
        return Modules::enabled('Shipping')
            ? ShippingMethod::query()->orderBy('sort')->pluck('name', 'code')->all() + ['pickup' => 'تحویل حضوری']
            : ['pickup' => 'تحویل حضوری', 'post' => 'پست'];
    }

    /** @return array<string, string> */
    public static function paymentOptions(): array
    {
        return PaymentMethods::titles() + ['cash' => 'نقدی / کارت‌خوان در فروشگاه', 'manual' => 'سایر'];
    }
}
