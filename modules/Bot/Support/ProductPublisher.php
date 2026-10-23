<?php

/**
 * Khaneh Romantic — From a bot draft to a real product: brand (new if needed), product, categories,
 * the model (price/stock), the product's image folder with the photos uploaded into it (named after
 * the product address, alt = product name), attributes, search index.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Bot\Support;

use App\Core\Modules\Modules;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Attributes\Models\AttributeValue;
use Modules\Attributes\Models\ProductAttribute;
use Modules\Attributes\Support\ProductAttributeValues;
use Modules\Bot\Models\BotDraft;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\FileManager\Support\FolderManager;
use Modules\Search\Support\SearchSync;
use RuntimeException;

final class ProductPublisher
{
    /** required before publishing: field => question label */
    public const REQUIRED = ['photos' => 'عکس', 'name' => 'نام', 'brand' => 'برند', 'category_ids' => 'دسته', 'price' => 'قیمت', 'stock' => 'موجودی', 'sku' => 'کد محصول'];

    /** @return list<string> missing required fields, in asking order */
    public static function missing(BotDraft $draft): array
    {
        $missing = [];
        foreach (array_keys(self::REQUIRED) as $field) {
            $has = match ($field) {
                'photos' => count((array) $draft->photos) > 0,
                'brand' => $draft->get('brand_id') || ($draft->get('brand_text') && $draft->get('brand_en')),
                'stock' => $draft->get('stock') !== null,
                default => filled($draft->get($field)),
            };
            if (! $has) {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    public static function skuTaken(string $sku, ?int $ignoreProduct = null): bool
    {
        return Product::withTrashed()->where('sku', $sku)->when($ignoreProduct, fn ($q) => $q->whereKeyNot($ignoreProduct))->exists()
            || ProductVariant::query()->where('sku', $sku)->exists();
    }

    /** a free code: BRAND-1001, BRAND-1002… */
    public static function suggestSku(?int $brandId, ?string $brandEn = null): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^a-z]/i', '', (string) ($brandId ? Brand::query()->whereKey($brandId)->value('name_en') : $brandEn)) ?: 'KR', 0, 3));
        $n = 1001;
        while (self::skuTaken($prefix.'-'.$n)) {
            $n++;
        }

        return $prefix.'-'.$n;
    }

    public function publish(BotDraft $draft, bool $active = true): Product
    {
        if ($missing = self::missing($draft)) {
            throw new RuntimeException('هنوز کامل نیست: '.implode('، ', array_map(fn ($f) => self::REQUIRED[$f], $missing)));
        }
        $sku = (string) $draft->get('sku');
        if (self::skuTaken($sku)) {
            throw new RuntimeException('کد «'.$sku.'» قبلاً برای محصول دیگری ثبت شده.');
        }

        $product = DB::transaction(function () use ($draft, $sku, $active) {
            $brand = $draft->get('brand_id') ? Brand::query()->findOrFail($draft->get('brand_id')) : Brand::query()->firstOrCreate(
                ['name_fa' => $draft->get('brand_text')],
                ['name_en' => $draft->get('brand_en'), 'slug' => Str::slug((string) $draft->get('brand_en'))],
            );

            $description = (string) $draft->get('description');
            if ($description !== '' && ! str_contains($description, '<')) {
                $description = collect(preg_split('/\R{2,}|\R/u', $description))->filter()->map(fn ($p) => '<p>'.e(trim($p)).'</p>')->implode("\n");
            }

            $product = Product::query()->create([
                'brand_id' => $brand->id,
                'sku' => $sku,
                'name' => $draft->get('name'),
                'short_description' => $draft->get('short_description'),
                'description' => $description ?: null,
                'meta_title' => $draft->get('meta_title'),
                'meta_description' => $draft->get('meta_description'),
                'status' => $active ? Product::STATUS_ACTIVE : Product::STATUS_DRAFT,
            ]);

            $categories = array_values(array_unique(array_map('intval', (array) $draft->get('category_ids'))));
            $product->categories()->sync(collect($categories)->mapWithKeys(fn ($id, $i) => [$id => ['is_primary' => $i === 0, 'position' => 0]])->all());

            $price = (int) $draft->get('price') * 10;
            $sale = $draft->get('sale_price') ? (int) $draft->get('sale_price') * 10 : null;
            $product->variants()->create([
                'sku' => ProductVariant::query()->where('sku', $sku)->exists() ? $sku.'-1' : $sku,
                'price' => $price,
                'sale_price' => $sale && $sale < $price ? $sale : null,
                'stock' => (int) $draft->get('stock'),
                'is_active' => true,
            ]);

            $this->photos($draft, $product, $brand);
            $this->attributes($draft, $product);

            return $product;
        });

        $draft->update(['status' => 'published', 'product_id' => $product->id]);
        if (class_exists(SearchSync::class) && Modules::enabled('Search')) {
            app(SearchSync::class)->product($product->id);
        }

        return $product->refresh();
    }

    /** the product's folder (products/{brand}/{brand-sku}) with the photos uploaded into it, first = main image */
    private function photos(BotDraft $draft, Product $product, Brand $brand): void
    {
        $folders = app(FolderManager::class);
        $folder = $folders->forProduct($brand, $product->sku, $product->id);
        $position = 0;
        foreach ($draft->photoPaths() as $path) {
            $file = new UploadedFile($path, basename($path), null, null, true);
            $media = $folders->store($file, $folder, $product->name);
            $product->media()->attach($media->id, ['collection' => 'gallery', 'position' => $position++]);
        }
    }

    /** «جنس: گرانیت» → the attribute «جنس» with the value «گرانیت» (made if new) */
    private function attributes(BotDraft $draft, Product $product): void
    {
        if (! Modules::enabled('Attributes')) {
            return;
        }
        $sync = app(ProductAttributeValues::class);
        $library = ProductAttribute::query()->get(['id', 'name']);

        $chosen = [];
        foreach ((array) $draft->get('attributes', []) as $attributeId => $valueIds) {   // picked with buttons
            $chosen[(int) $attributeId] = array_map('intval', (array) $valueIds);
        }
        foreach ((array) $draft->get('attributes_text', []) as $name => $text) {        // typed / from AI
            $attribute = $library->first(fn ($a) => ProductText::norm($a->name) === ProductText::norm((string) $name));
            if (! $attribute || isset($chosen[$attribute->id])) {
                continue;
            }
            foreach (preg_split('/\s*[،,]\s*/u', (string) $text) as $value) {
                $value = trim($value);
                if ($value === '' || mb_strlen($value) > 60) {
                    continue;
                }
                $existing = AttributeValue::query()->where('attribute_id', $attribute->id)->get(['id', 'value'])
                    ->first(fn ($v) => ProductText::norm($v->value) === ProductText::norm($value));
                $chosen[$attribute->id][] = $existing?->id
                    ?? AttributeValue::query()->create(['attribute_id' => $attribute->id, 'value' => $value])->id;
            }
        }
        foreach ($chosen as $attributeId => $valueIds) {
            $sync->sync($product, $attributeId, $valueIds);
        }
    }
}
