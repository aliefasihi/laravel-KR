<?php

/**
 * Khaneh Romantic — In the bot: find a product and change its price, sale price, stock or status;
 * today's orders; low stock
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Bot\Support;

use App\Core\Modules\Modules;
use App\Core\Support\Money;
use App\Core\Support\PersianText;
use Illuminate\Support\Facades\Storage;
use Modules\Bot\Models\BotOperator;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Inventory\Support\LowStock;
use Modules\Order\Models\Order;
use Modules\Search\Support\SearchSync;

final class ProductTools
{
    public function __construct(private BotOperator $op, private Out $out) {}

    public function askSearch(): void
    {
        $this->op->goTo('find');
        $this->out->say('🔎 نام یا کد محصول را بفرستید.');
    }

    public function search(string $query): void
    {
        $term = trim(PersianText::toLatinDigits($query));
        $like = '%'.PersianText::normalize($term).'%';
        $products = Product::query()->with('variants')
            ->where(fn ($q) => $q->where('sku', $term)->orWhere('sku', 'like', '%'.$term.'%')->orWhere('name', 'like', $like))
            ->orderByRaw('sku = ? desc', [$term])->limit(8)->get();

        if ($products->isEmpty()) {
            $this->out->say('چیزی پیدا نشد؛ نام یا کد دیگری بفرستید.');

            return;
        }
        $this->op->goTo('idle');
        if ($products->count() === 1) {
            $this->card($products->first());

            return;
        }
        $this->out->say('کدام؟', Out::buttons($products->map(fn ($p) => [[mb_substr($p->name, 0, 40).' · '.$p->sku, 'p:open:'.$p->id]])->all()));
    }

    public function card(Product $product): void
    {
        $product->loadMissing('variants');
        $lines = ['📦 '.$product->name, '🔢 '.$product->sku.' · '.(Product::STATUSES[$product->status] ?? $product->status)];
        $rows = [];
        foreach ($product->variants as $i => $v) {
            $label = $product->variants->count() > 1 ? 'مدل '.PersianText::toPersianDigits($i + 1).' ('.($v->options ? implode(' ', $v->options) : $v->sku).')' : '';
            $lines[] = trim($label.' 💰 '.Money::toman($v->price).' تومان'.($v->sale_price ? ' ← حراج '.Money::toman($v->sale_price) : '').' · 📦 '.PersianText::toPersianDigits($v->stock));
            $suffix = $product->variants->count() > 1 ? ' '.PersianText::toPersianDigits($i + 1) : '';
            $rows[] = [['💰 قیمت'.$suffix, 'p:price:'.$v->id], ['🏷 حراج'.$suffix, 'p:sale:'.$v->id], ['📦 موجودی'.$suffix, 'p:stock:'.$v->id]];
        }
        $rows[] = [[$product->status === Product::STATUS_ACTIVE ? '🙈 برداشتن از سایت' : '👁 انتشار', 'p:status:'.$product->id]];
        $lines[] = '🔗 '.url($product->url());

        $photo = $product->media()->first();
        $path = $photo ? Storage::disk($photo->disk)->path($photo->path) : null;
        $path && is_file($path)
            ? $this->out->photo($path, implode("\n", $lines), Out::buttons($rows))
            : $this->out->say(implode("\n", $lines), Out::buttons($rows));
    }

    /** p:{action}:{id} */
    public function button(string $data): void
    {
        [, $action, $id] = array_pad(explode(':', $data, 3), 3, null);
        $id = (int) $id;
        match ($action) {
            'open' => ($p = Product::query()->find($id)) ? $this->card($p) : $this->out->say('پیدا نشد.'),
            'price', 'sale', 'stock' => $this->askValue($action, $id),
            'status' => $this->toggleStatus($id),
            default => null,
        };
    }

    private function askValue(string $field, int $variantId): void
    {
        $variant = ProductVariant::query()->find($variantId);
        if (! $variant) {
            $this->out->say('پیدا نشد.');

            return;
        }
        $this->op->goTo('pedit', ['field' => $field, 'variant' => $variantId]);
        $this->out->say(match ($field) {
            'price' => '💰 قیمت جدید به تومان؟ (الان '.Money::toman($variant->price).')',
            'sale' => '🏷 قیمت حراج به تومان؟ (۰ = برداشتن حراج)',
            default => '📦 موجودی جدید؟ (الان '.PersianText::toPersianDigits($variant->stock).')',
        });
    }

    public function value(string $text): void
    {
        $field = $this->op->ctx('field');
        $variant = ProductVariant::query()->with('product')->find((int) $this->op->ctx('variant'));
        if (! $variant) {
            $this->op->goTo('idle');

            return;
        }
        if ($field === 'stock') {
            $n = Numbers::count($text);
            if ($n === null) {
                $this->out->say('⚠️ یک عدد بفرستید.');

                return;
            }
            $variant->update(['stock' => $n]);
        } else {
            $toman = Numbers::toman($text);
            if ($toman === null || ($field === 'price' && $toman < 1000)) {
                $this->out->say('⚠️ قیمت را متوجه نشدم (به تومان).');

                return;
            }
            $rial = $toman * 10;
            if ($field === 'price') {
                $variant->update(['price' => $rial, 'sale_price' => $variant->sale_price && $variant->sale_price < $rial ? $variant->sale_price : null]);
            } elseif ($rial === 0) {
                $variant->update(['sale_price' => null]);
            } elseif ($rial >= $variant->price) {
                $this->out->say('⚠️ قیمت حراج باید از قیمت اصلی ('.Money::toman($variant->price).') کمتر باشد.');

                return;
            } else {
                $variant->update(['sale_price' => $rial]);
            }
        }
        $variant->product->touch();
        if (class_exists(SearchSync::class) && Modules::enabled('Search')) {
            app(SearchSync::class)->product($variant->product_id);
        }
        $this->op->goTo('idle');
        $this->out->say('✅ ذخیره شد.');
        $this->card($variant->product->refresh());
    }

    private function toggleStatus(int $productId): void
    {
        $product = Product::query()->find($productId);
        if (! $product) {
            return;
        }
        $product->update(['status' => $product->status === Product::STATUS_ACTIVE ? Product::STATUS_DRAFT : Product::STATUS_ACTIVE]);
        $this->out->say($product->status === Product::STATUS_ACTIVE ? '👁 منتشر شد.' : '🙈 از سایت برداشته شد (پیش‌نویس).');
    }

    public function orders(): void
    {
        if (! Modules::enabled('Order')) {
            $this->out->say('ماژول سفارش خاموش است.');

            return;
        }
        $orders = Order::query()->where('created_at', '>=', today())->latest('id')->get();
        $counted = $orders->whereNotIn('status', ['pending', 'cancelled', 'failed']);
        $lines = ['📦 سفارش‌های امروز: '.PersianText::toPersianDigits($orders->count()).' (قطعی: '.PersianText::toPersianDigits($counted->count()).')',
            '💰 جمع قطعی‌ها: '.Money::toman((int) $counted->sum('total')).' تومان', ''];
        foreach ($orders->take(10) as $o) {
            $lines[] = '#'.PersianText::toPersianDigits((string) $o->number).' · '.($o->customer_name ?: $o->recipient_name).' · '.Money::toman($o->total).' · '.(Order::STATUSES[$o->status] ?? $o->status);
        }
        $this->out->say(implode("\n", $lines));
    }

    public function lowStock(): void
    {
        if (! Modules::enabled('Inventory') || ! class_exists(LowStock::class)) {
            $variants = ProductVariant::query()->with('product')->where('is_active', true)->where('stock', '<=', 2)->orderBy('stock')->limit(15)->get();
        } else {
            $variants = LowStock::query()->with('product')->limit(15)->get();
        }
        if ($variants->isEmpty()) {
            $this->out->say('✅ موجودی همه‌ی کالاها خوب است.');

            return;
        }
        $this->out->say('⚠️ موجودی کم:', Out::buttons($variants->map(fn ($v) => [[
            mb_substr((string) $v->product?->name, 0, 32).' · '.PersianText::toPersianDigits($v->stock).' عدد', 'p:open:'.$v->product_id,
        ]])->all()));
    }
}
