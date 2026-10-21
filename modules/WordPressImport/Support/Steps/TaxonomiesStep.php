<?php

/**
 * Khaneh Romantic — Brands, product categories (same URLs) and attributes (pa_*)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\WordPressImport\Support\Steps;

use App\Core\Modules\Modules;
use App\Core\Support\StoreCache;
use App\Core\Support\UrlPath;
use Illuminate\Support\Str;
use Modules\Attributes\Models\ProductAttribute;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\WordPressImport\Support\AttributeGroupsImport;
use Modules\WordPressImport\Support\AttributeLabels;
use Modules\WordPressImport\Support\MediaImporter;
use Modules\WordPressImport\Support\Report;
use Modules\WordPressImport\Support\Source;

class TaxonomiesStep extends Step
{
    public function __construct(Source $wp, private MediaImporter $media)
    {
        parent::__construct($wp);
    }

    public function key(): string
    {
        return 'taxonomies';
    }

    public function label(): string
    {
        return 'برندها، دسته‌ها، ویژگی‌ها و گروه‌های ویژگی';
    }

    public function sourceCount(): int
    {
        $brand = $this->wp->firstTaxonomy(config('modules.wordpressimport.brand_taxonomies'));

        return $this->wp->table('term_taxonomy')->whereIn('taxonomy', array_filter(['product_cat', $brand]))->count();
    }

    public function importedCount(): int
    {
        return Category::query()->whereNotNull('legacy_term_id')->count() + Brand::query()->whereNotNull('legacy_term_id')->count();
    }

    public function run(Report $report): void
    {
        $this->brands($report);
        $this->categories($report);
        if (Modules::enabled('Attributes')) {
            $this->attributes($report);
            app(AttributeGroupsImport::class, ['wp' => $this->wp])->run($report);
        }
        StoreCache::flush();
    }

    private function brands(Report $report): void
    {
        $taxonomy = $this->wp->firstTaxonomy(config('modules.wordpressimport.brand_taxonomies'));
        if (! $taxonomy) {
            $report->warn('taxonomies', 'تکسونومی برند پیدا نشد؛ همه‌ی محصولات زیر برند پیش‌فرض می‌روند.');

            return;
        }
        $report->info('taxonomies', "برندها از «{$taxonomy}»");
        $fixed = config('modules.wordpressimport.brand_slugs', []);

        foreach ($this->wp->terms($taxonomy) as $term) {
            $name = html_entity_decode($term->name);
            $decoded = rawurldecode($term->slug);
            $ascii = preg_match('/^[a-z0-9-]+$/i', $decoded) ? strtolower($decoded) : null;
            $slug = $fixed[$name]['slug'] ?? $ascii ?? (Str::slug($name) ?: 'brand-'.$term->term_id);
            $nameEn = $fixed[$name]['name_en'] ?? ($ascii ? Str::headline($ascii) : Str::headline($slug));

            $existing = Brand::query()->where('legacy_term_id', $term->term_id)->first()
                ?? Brand::query()->where('slug', $slug)->first();
            $report->count('taxonomies', $existing ? 'updated' : 'created');
            if ($report->dryRun) {
                continue;
            }
            $brand = $existing ?? new Brand;
            $brand->fill([
                'name_fa' => $name,
                'name_en' => $brand->name_en ?: $nameEn,
                'slug' => $brand->slug ?: $slug,
                'description' => $term->description ?: $brand->description,
            ]);
            $brand->legacy_term_id = $term->term_id;
            $brand->save();
        }
    }

    private function categories(Report $report): void
    {
        $terms = $this->wp->terms('product_cat');
        $meta = $this->wp->termMeta($terms->keys()->all());

        // full decoded path for every term: parent-slug/child-slug
        $path = function (int $id, int $depth = 0) use (&$path, $terms): string {
            $term = $terms[$id];

            return ($term->parent && isset($terms[$term->parent]) && $depth < 10 ? $path((int) $term->parent, $depth + 1).'/' : '').rawurldecode($term->slug);
        };

        $done = [];
        foreach ($terms->sortBy(fn ($t) => substr_count($path((int) $t->term_id), '/')) as $term) {
            $termMeta = $meta[$term->term_id] ?? [];
            $existing = Category::query()->where('legacy_term_id', $term->term_id)->first()
                ?? Category::query()->where('path_hash', UrlPath::hash($path((int) $term->term_id)))->first();
            $report->count('taxonomies', $existing ? 'updated' : 'created');
            if ($report->dryRun) {
                continue;
            }

            $category = $existing ?? new Category;
            $category->fill([
                'name' => html_entity_decode($term->name),
                'path' => $path((int) $term->term_id),
                'parent_id' => $term->parent ? ($done[$term->parent] ?? null) : null,
                'description' => $term->description ?: $category->description,
                'meta_title' => $termMeta['rank_math_title'] ?? $category->meta_title,
                'meta_description' => isset($termMeta['rank_math_description']) ? Str::limit(strip_tags($termMeta['rank_math_description']), 490, '') : $category->meta_description,
                'sort' => (int) ($termMeta['order'] ?? $category->sort ?? 0),
                'is_active' => true,
            ]);
            $category->legacy_term_id = $term->term_id;
            $category->save();
            $done[$term->term_id] = $category->id;

            if (! empty($termMeta['thumbnail_id']) && $category->media()->doesntExist()) {
                if ($image = $this->media->fromAttachment((int) $termMeta['thumbnail_id'], $report, $category->name)) {
                    $category->media()->attach($image->id, ['collection' => 'cover', 'position' => 0]);
                }
            }
        }
    }

    /** woocommerce_attribute_taxonomies + their pa_* terms → attribute library */
    private function attributes(Report $report): void
    {
        if (! $this->wp->hasTable('woocommerce_attribute_taxonomies')) {
            return;
        }
        foreach ($this->wp->table('woocommerce_attribute_taxonomies')->get() as $row) {
            $taxonomy = 'pa_'.$row->attribute_name;
            $terms = $this->wp->terms($taxonomy);
            $meta = $this->wp->termMeta($terms->keys()->all());
            $slug = preg_match('/^[a-z0-9_-]+$/i', rawurldecode($row->attribute_name)) ? strtolower(rawurldecode($row->attribute_name)) : null;
            $found = ProductAttribute::query()->where('slug', Str::slug($slug ?: $row->attribute_label))->first();
            $report->count('attributes', $found ? 'updated' : 'created');
            if ($report->dryRun) {
                continue;
            }

            $attribute = $found
                ?? ProductAttribute::create([
                    'name' => AttributeLabels::clean((string) $row->attribute_label),
                    'slug' => $slug ?: $row->attribute_label,
                    'is_filterable' => true,
                    'is_visible' => true,
                ]);

            $sort = 0;
            foreach ($terms as $term) {
                $color = collect($meta[$term->term_id] ?? [])->first(fn ($v, $k) => str_contains((string) $k, 'color') && is_string($v) && preg_match('/^#[0-9a-f]{3,8}$/i', $v));
                $attribute->values()->updateOrCreate(['value' => html_entity_decode($term->name)], ['sort' => $sort++] + ($color ? ['color' => $color] : []));
                if ($color && $attribute->display !== 'color') {
                    $attribute->update(['display' => 'color']);
                }
            }
        }
    }
}
