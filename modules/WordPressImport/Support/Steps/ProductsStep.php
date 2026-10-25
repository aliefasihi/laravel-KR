<?php

/**
 * Khaneh Romantic — WooCommerce products and variations
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 *  simple product   → one model (variant) with the product's price/stock
 *  variable product → one model per variation, options {«رنگ»: «طلایی»}
 *  images           → featured image first, then the gallery
 *  attributes (pa_* and custom) → Attributes module values (filters + specs table)
 * New address: /p/{brand}-{sku}; the old one gets a 301 in the redirects step.
 */

namespace Modules\WordPressImport\Support\Steps;

use App\Core\Modules\Modules;
use App\Core\Support\StoreCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Attributes\Models\ProductAttribute;
use Modules\Attributes\Support\ProductAttributeValues;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\WordPressImport\Support\AttributeLabels;
use Modules\WordPressImport\Support\MediaImporter;
use Modules\WordPressImport\Support\Report;
use Modules\WordPressImport\Support\Source;

class ProductsStep extends Step
{
    private const META = ['_sku', '_regular_price', '_sale_price', '_sale_price_dates_from', '_sale_price_dates_to', '_price',
        '_manage_stock', '_stock', '_stock_status', '_thumbnail_id', '_product_image_gallery', '_product_attributes',
        'rank_math_title', 'rank_math_description', 'rank_math_primary_product_cat', '_yoast_wpseo_title', '_yoast_wpseo_metadesc', '_yoast_wpseo_primary_product_cat'];

    public function __construct(Source $wp, private MediaImporter $media)
    {
        parent::__construct($wp);
    }

    public function key(): string
    {
        return 'products';
    }

    public function label(): string
    {
        return 'محصولات، مدل‌ها و تصاویرشان';
    }

    public function sourceCount(): int
    {
        return $this->wp->table('posts')->where('post_type', 'product')->whereIn('post_status', ['publish', 'draft', 'pending', 'private'])->count();
    }

    public function importedCount(): int
    {
        return Product::query()->whereNotNull('legacy_post_id')->count();
    }

    public function run(Report $report): void
    {
        $brandTaxonomy = $this->wp->firstTaxonomy(config('modules.wordpressimport.brand_taxonomies'));
        $brands = Brand::query()->whereNotNull('legacy_term_id')->pluck('id', 'legacy_term_id')->all();
        $categories = Category::query()->whereNotNull('legacy_term_id')->pluck('id', 'legacy_term_id')->all();

        $query = $this->wp->table('posts')->where('post_type', 'product')->whereIn('post_status', ['publish', 'draft', 'pending', 'private'])->orderBy('ID');
        if ($report->limit) {
            $query->limit($report->limit);
        }

        $query->chunk(100, function ($posts) use ($report, $brandTaxonomy, $brands, $categories) {
            $ids = $posts->pluck('ID')->map(fn ($id) => (int) $id)->all();
            $meta = $this->wp->postMeta($ids, self::META);
            $types = $this->wp->objectTerms('product_type', $ids);
            $typeNames = $this->wp->terms('product_type')->pluck('slug', 'term_id')->all();
            $productCats = $this->wp->objectTerms('product_cat', $ids);
            $productBrands = $brandTaxonomy ? $this->wp->objectTerms($brandTaxonomy, $ids) : [];
            $variations = $this->wp->table('posts')->where('post_type', 'product_variation')->whereIn('post_parent', $ids)
                ->whereIn('post_status', ['publish', 'private'])->orderBy('menu_order')->orderBy('ID')->get()->groupBy('post_parent');
            $variationMeta = $this->wp->postMeta($variations->flatten()->pluck('ID')->map(fn ($id) => (int) $id)->all());

            foreach ($posts as $post) {
                $id = (int) $post->ID;
                try {
                    $type = $typeNames[$types[$id][0] ?? 0] ?? 'simple';
                    $this->product($post, $meta[$id] ?? [], $type, $variations->get($id, collect()), $variationMeta,
                        ($productCats[$id] ?? []), $brands[$productBrands[$id][0] ?? 0] ?? null, $categories, $report);
                } catch (\Throwable $e) {
                    $report->count('products', 'failed');
                    $report->warn('products', "محصول {$id} «{$post->post_title}»: ".$e->getMessage());
                }
            }
        });

        StoreCache::flush();
    }

    private function product(object $post, array $meta, string $type, $variations, array $variationMeta, array $catTermIds, ?int $brandId, array $categories, Report $report): void
    {
        $id = (int) $post->ID;
        $existing = Product::query()->withTrashed()->where('legacy_post_id', $id)->first();

        if ($type === 'variable' && $variations->isEmpty()) {
            $report->count('products', 'skipped');
            $report->warn('products', "محصول متغیر {$id} «{$post->post_title}» مدلی ندارد؛ رد شد.");

            return;
        }
        $report->count('products', $existing ? 'updated' : 'created');
        if (trim((string) ($meta['_sku'] ?? '')) === '' && ! $existing?->sku) {
            $report->count('products', 'no_sku');
        }
        if ($report->dryRun) {
            return;
        }

        // no SKU in WooCommerce: a draft without an address until one is entered (never a made-up code)
        $wpSku = trim((string) ($meta['_sku'] ?? ''));
        $sku = $wpSku !== '' ? $this->uniqueProductSku($wpSku, $existing?->id) : null;

        DB::transaction(function () use ($post, $meta, $type, $variations, $variationMeta, $catTermIds, $brandId, $categories, $existing, $sku, $id, $report) {
            $product = $existing ?? new Product;
            $product->fill([
                'brand_id' => $brandId ?? $this->defaultBrand()->id,
                'sku' => $existing?->sku ?: $sku,
                'name' => html_entity_decode($post->post_title),
                'short_description' => $post->post_excerpt ?: null,
                'description' => $post->post_content ?: null,
                'status' => $post->post_status === 'publish' && ($existing?->sku ?: $sku) !== null ? Product::STATUS_ACTIVE : Product::STATUS_DRAFT,
                'meta_title' => $this->seoText($meta['rank_math_title'] ?? $meta['_yoast_wpseo_title'] ?? null),
                'meta_description' => ($d = $this->seoText($meta['rank_math_description'] ?? $meta['_yoast_wpseo_metadesc'] ?? null)) ? Str::limit($d, 490, '') : null,
            ]);
            $product->legacy_post_id = $id;
            $product->created_at = $this->date($post->post_date_gmt) ?? now();
            $product->save();
            if ($product->trashed()) {
                $product->restore();
            }

            // categories, primary first
            $primary = (int) ($meta['rank_math_primary_product_cat'] ?? $meta['_yoast_wpseo_primary_product_cat'] ?? 0) ?: ($catTermIds[0] ?? 0);
            $sync = [];
            foreach ($catTermIds as $position => $termId) {
                if (isset($categories[$termId])) {
                    $sync[$categories[$termId]] = ['is_primary' => $termId === $primary, 'position' => $position];
                }
            }
            if ($sync && ! in_array(true, array_column($sync, 'is_primary'), true)) {
                $sync[array_key_first($sync)]['is_primary'] = true;
            }
            $product->categories()->sync($sync);

            // models
            $keep = [];
            if ($type === 'variable') {
                foreach ($variations as $variation) {
                    $vm = $variationMeta[(int) $variation->ID] ?? [];
                    $keep[] = $this->variant($product, (int) $variation->ID, $vm + ['_parent_sku' => $product->sku], $this->variationOptions($vm, $meta), $variation->post_status === 'publish')->id;
                }
            } else {
                $keep[] = $this->variant($product, $id, $meta + ['_sku' => $product->sku], null, true)->id;
            }
            $product->variants()->whereNotIn('id', $keep)->whereNotNull('legacy_post_id')->update(['is_active' => false]);

            // images: featured + gallery
            $attachmentIds = array_values(array_unique(array_filter(array_merge(
                [(int) ($meta['_thumbnail_id'] ?? 0)],
                array_map('intval', explode(',', (string) ($meta['_product_image_gallery'] ?? ''))),
            ))));
            if ($attachmentIds && $product->media()->doesntExist()) {
                $this->media->warm($attachmentIds);
                $position = 0;
                foreach ($attachmentIds as $attachmentId) {
                    if ($media = $this->media->fromAttachment($attachmentId, $report, $product->name)) {
                        $product->media()->syncWithoutDetaching([$media->id => ['collection' => 'gallery', 'position' => $position++]]);
                    }
                }
            }

            if (Modules::enabled('Attributes')) {
                $this->attributes($product, (array) ($meta['_product_attributes'] ?? []));
            }
        });
    }

    private function variant(Product $product, int $legacyId, array $meta, ?array $options, bool $active): ProductVariant
    {
        $variant = ProductVariant::query()->where('legacy_post_id', $legacyId)->first() ?? new ProductVariant;
        $regular = $this->rials($meta['_regular_price'] ?? null) ?? $this->rials($meta['_price'] ?? null) ?? 0;
        $sale = $this->rials($meta['_sale_price'] ?? null);
        $manage = ($meta['_manage_stock'] ?? 'no') === 'yes';
        $inStock = ($meta['_stock_status'] ?? 'instock') !== 'outofstock';

        // own SKU, else the product's (made unique: SKU, SKU-2…); a product without one leaves it empty
        $sku = trim((string) ($meta['_sku'] ?? '')) ?: (string) ($meta['_parent_sku'] ?? '');
        $sku = $variant->sku ?: ($sku !== '' ? $this->uniqueVariantSku($sku, $variant->id) : null);

        $variant->fill([
            'product_id' => $product->id,
            'sku' => $sku,
            'options' => $options ?: null,
            'price' => max($regular, (int) $sale),
            'sale_price' => $sale !== null && $sale > 0 && $sale < $regular ? $sale : null,
            'sale_starts_at' => $this->date($meta['_sale_price_dates_from'] ?? null),
            'sale_ends_at' => $this->date($meta['_sale_price_dates_to'] ?? null),
            'stock' => $manage ? max(0, (int) ($meta['_stock'] ?? 0)) : ($inStock ? (int) config('modules.wordpressimport.unmanaged_stock') : 0),
            'is_active' => $active,
        ]);
        $variant->legacy_post_id = $legacyId;
        $variant->save();

        return $variant;
    }

    /** attribute_pa_color = "talaei" → {«رنگ»: «طلایی»} */
    private function variationOptions(array $variationMeta, array $parentMeta): array
    {
        $options = [];
        $defs = (array) ($parentMeta['_product_attributes'] ?? []);
        foreach ($variationMeta as $key => $value) {
            if (! str_starts_with((string) $key, 'attribute_') || $value === '' || $value === null) {
                continue;
            }
            $name = substr($key, strlen('attribute_'));
            if (str_starts_with($name, 'pa_')) {
                $label = $this->taxonomyLabel($name);
                $term = $this->wp->table('terms as t')->join('term_taxonomy as tt', 'tt.term_id', '=', 't.term_id')
                    ->where('tt.taxonomy', $name)->where('t.slug', $value)->value('t.name');
                $options[$label] = html_entity_decode($term ?? rawurldecode((string) $value));
            } else {
                $label = AttributeLabels::clean($defs[$name]['name'] ?? $defs[rawurldecode($name)]['name'] ?? rawurldecode($name));
                $options[$label] = rawurldecode((string) $value);
            }
        }

        return $options;
    }

    /** @var array<string, string> */
    private array $labels = [];

    private function taxonomyLabel(string $taxonomy): string
    {
        if (! isset($this->labels[$taxonomy])) {
            $this->labels[$taxonomy] = AttributeLabels::clean($this->wp->hasTable('woocommerce_attribute_taxonomies')
                ? ($this->wp->table('woocommerce_attribute_taxonomies')->where('attribute_name', substr($taxonomy, 3))->value('attribute_label') ?? substr($taxonomy, 3))
                : substr($taxonomy, 3));
        }

        return $this->labels[$taxonomy];
    }

    private function attributes(Product $product, array $definitions): void
    {
        $values = app(ProductAttributeValues::class);
        foreach ($definitions as $key => $def) {
            $isTaxonomy = (bool) ($def['is_taxonomy'] ?? false);
            $label = $isTaxonomy ? $this->taxonomyLabel((string) ($def['name'] ?? $key)) : AttributeLabels::clean((string) ($def['name'] ?? $key));
            if ($label === '') {
                continue;
            }
            $labels = $isTaxonomy
                ? $this->wp->table('term_relationships as tr')->join('term_taxonomy as tt', 'tt.term_taxonomy_id', '=', 'tr.term_taxonomy_id')
                    ->join('terms as t', 't.term_id', '=', 'tt.term_id')
                    ->where('tt.taxonomy', $def['name'] ?? $key)->where('tr.object_id', $product->legacy_post_id)->pluck('t.name')->map(fn ($n) => html_entity_decode($n))->all()
                : array_values(array_filter(array_map('trim', explode('|', (string) ($def['value'] ?? '')))));
            if ($labels === []) {
                continue;
            }

            $attribute = ($isTaxonomy ? ProductAttribute::query()->where('slug', strtolower(rawurldecode(substr((string) ($def['name'] ?? $key), 3))))->first() : null)
                ?? ProductAttribute::query()->where('name', $label)->first()
                ?? ProductAttribute::create(['name' => $label, 'slug' => Str::slug($label) ?: null, 'is_filterable' => true, 'is_visible' => (bool) ($def['is_visible'] ?? true)]);
            $ids = array_map(fn ($v) => $attribute->values()->firstOrCreate(['value' => $v])->id, $labels);
            $values->sync($product, $attribute->id, $ids);
        }
    }

    private function defaultBrand(): Brand
    {
        $d = config('modules.wordpressimport.default_brand');

        return Brand::query()->firstOrCreate(['slug' => $d['slug']], ['name_fa' => $d['name_fa'], 'name_en' => $d['name_en']]);
    }

    private function uniqueProductSku(string $sku, ?int $ignoreId): string
    {
        $sku = Str::limit(trim($sku), 60, '');
        $candidate = $sku;
        for ($i = 2; Product::query()->withTrashed()->where('sku', $candidate)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists(); $i++) {
            $candidate = $sku.'-'.$i;
        }

        return $candidate;
    }

    private function uniqueVariantSku(string $sku, ?int $ignoreId): string
    {
        $sku = Str::limit(trim($sku), 60, '');
        $candidate = $sku;
        for ($i = 2; ProductVariant::query()->where('sku', $candidate)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists(); $i++) {
            $candidate = $sku.'-'.$i;
        }

        return $candidate;
    }

    /** Rank Math/Yoast titles may hold variables like %title% %sep% %sitename% — drop those. */
    private function seoText(?string $text): ?string
    {
        if (! $text || str_contains($text, '%')) {
            return null;
        }

        return html_entity_decode(trim($text)) ?: null;
    }
}
