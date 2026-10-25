<?php

/**
 * Khaneh Romantic — 301s from every old WordPress address to its new home
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Products (WooCommerce product base, also with %product_cat%), posts (the site's permalink
 * structure), pages (page_redirects map), product categories when their base differs from ours,
 * brands, product tags (→ search), blog categories/tags (→ blog), ?p= / ?page_id= / ?product= /
 * ?product_cat= links, and every uploaded image size (→ the imported file). Category URLs on the
 * same base need nothing: they are kept exactly.
 */

namespace Modules\WordPressImport\Support\Steps;

use App\Core\Support\UrlPath;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Content\Models\Post;
use Modules\Media\Models\Media;
use Modules\Seo\Models\Redirect;
use Modules\WordPressImport\Support\MediaImporter;
use Modules\WordPressImport\Support\Report;
use Modules\WordPressImport\Support\Source;

class RedirectsStep extends Step
{
    public function __construct(Source $wp, private MediaImporter $media)
    {
        parent::__construct($wp);
    }

    public function key(): string
    {
        return 'redirects';
    }

    public function label(): string
    {
        return 'ریدایرکت آدرس‌های قدیمی (۳۰۱)';
    }

    public function requires(): array
    {
        return ['Seo'];
    }

    public function sourceCount(): int
    {
        return Product::withTrashed()->whereNotNull('legacy_post_id')->count() + Post::query()->whereNotNull('legacy_post_id')->count()
            + Media::query()->whereNotNull('legacy_url_hash')->count();
    }

    public function importedCount(): int
    {
        return Redirect::query()->where('source', 'import')->count();
    }

    public function run(Report $report): void
    {
        $this->products($report);
        $this->posts($report);
        $this->pages($report);
        $this->taxonomies($report);
        $this->images($report);
    }

    /** @var array<string, true> */
    private array $seen = [];

    private function point(string $from, string $to, Report $report, int $status = 301): void
    {
        $from = UrlPath::normalize($from);
        if ($from === '' || $from === UrlPath::normalize($to) || isset($this->seen[$from])) {
            return;
        }
        $this->seen[$from] = true;
        $report->count('redirects', Redirect::query()->where('from_hash', UrlPath::hash($from))->exists() ? 'updated' : 'created');
        if (! $report->dryRun) {
            Redirect::point($from, $to, 'import', $status);
        }
    }

    /** "/product/" or "/shop/%product_cat%" → trimmed decoded base */
    private function base(?string $value, string $default): string
    {
        return trim(rawurldecode((string) ($value ?: $default)), '/');
    }

    private function products(Report $report): void
    {
        $permalinks = (array) $this->wp->option('woocommerce_permalinks', []);
        $base = $this->base($permalinks['product_base'] ?? null, 'product');
        $withCategory = str_contains($base, '%product_cat%');
        $categoryPaths = $withCategory ? $this->termPaths('product_cat') : [];

        Product::withTrashed()->whereNotNull('legacy_post_id')->with('categories')
            ->when($report->limit, fn ($q, $l) => $q->limit($l))
            ->chunkById(200, function ($products) use ($report, $base, $withCategory, $categoryPaths) {
                $posts = $this->wp->table('posts')->whereIn('ID', $products->pluck('legacy_post_id')->all())->get(['ID', 'post_name'])->keyBy('ID');
                $terms = $withCategory ? $this->wp->objectTerms('product_cat', $products->pluck('legacy_post_id')->all()) : [];
                foreach ($products as $product) {
                    // no SKU yet = no address: its category for now; run this step again once SKUs are in
                    $to = match (true) {
                        $product->trashed() => '/shop',
                        $product->slug !== null => Product::pathFor($product->slug),
                        default => parse_url((string) $product->primaryCategory()?->url(), PHP_URL_PATH) ?: '/shop',
                    };
                    $name = rawurldecode((string) ($posts[$product->legacy_post_id]->post_name ?? ''));
                    if ($name !== '') {
                        if ($withCategory) {
                            foreach ($terms[$product->legacy_post_id] ?? [] as $termId) {
                                $this->point(str_replace('%product_cat%', $categoryPaths[$termId] ?? '', $base).'/'.$name, $to, $report);
                            }
                            $this->point(str_replace('%product_cat%', 'uncategorized', $base).'/'.$name, $to, $report);
                        } else {
                            $this->point($base.'/'.$name, $to, $report);
                            foreach ((array) config('modules.seo.legacy_product_prefixes', []) as $prefix) {
                                $this->point($prefix.'/'.$name, $to, $report);
                            }
                        }
                        $this->point('?product='.$name, $to, $report);
                    }
                    $this->point('?p='.$product->legacy_post_id, $to, $report);
                }
            });
    }

    private function posts(Report $report): void
    {
        $structure = trim((string) $this->wp->option('permalink_structure', '/%postname%/'), '/') ?: '?p=%post_id%';
        $categories = $this->termPaths('category');

        Post::query()->whereNotNull('legacy_post_id')->when($report->limit, fn ($q, $l) => $q->limit($l))
            ->chunkById(200, function ($posts) use ($report, $structure, $categories) {
                $wp = $this->wp->table('posts')->whereIn('ID', $posts->pluck('legacy_post_id')->all())->get(['ID', 'post_name', 'post_date', 'post_author'])->keyBy('ID');
                $terms = str_contains($structure, '%category%') ? $this->wp->objectTerms('category', $posts->pluck('legacy_post_id')->all()) : [];
                $authors = str_contains($structure, '%author%')
                    ? $this->wp->table('users')->whereIn('ID', $wp->pluck('post_author')->unique()->all())->pluck('user_nicename', 'ID')->all() : [];
                foreach ($posts as $post) {
                    $row = $wp[$post->legacy_post_id] ?? null;
                    $to = '/'.ltrim(parse_url($post->url(), PHP_URL_PATH) ?: '/', '/');
                    $this->point('?p='.$post->legacy_post_id, $to, $report);
                    if (! $row || str_starts_with($structure, '?')) {
                        continue;
                    }
                    $date = Carbon::parse($row->post_date);
                    $from = strtr($structure, [
                        '%postname%' => rawurldecode((string) $row->post_name),
                        '%post_id%' => (string) $row->ID,
                        '%year%' => $date->format('Y'), '%monthnum%' => $date->format('m'), '%day%' => $date->format('d'),
                        '%hour%' => $date->format('H'), '%minute%' => $date->format('i'), '%second%' => $date->format('s'),
                        '%category%' => $categories[($terms[$row->ID] ?? [0])[0] ?? 0] ?? 'uncategorized',
                        '%author%' => (string) ($authors[$row->post_author] ?? ''),
                    ]);
                    $this->point($from, $to, $report);
                }
            });
    }

    /** Pages aren't imported; the known ones (about, contact, shop…) point to their new address. */
    private function pages(Report $report): void
    {
        $map = collect(config('modules.wordpressimport.page_redirects', []))->mapWithKeys(fn ($to, $slug) => [UrlPath::normalize($slug) => $to]);
        $pages = $this->wp->table('posts')->where('post_type', 'page')->where('post_status', 'publish')->get(['ID', 'post_name', 'post_parent'])->keyBy('ID');
        $path = function (int $id, int $depth = 0) use (&$path, $pages): string {
            $page = $pages[$id] ?? null;

            return ! $page ? '' : ltrim(($page->post_parent && $depth < 10 ? $path((int) $page->post_parent, $depth + 1).'/' : '').rawurldecode((string) $page->post_name), '/');
        };

        foreach ($pages as $page) {
            $full = UrlPath::normalize($path((int) $page->ID));
            $to = $map[$full] ?? $map[UrlPath::normalize(rawurldecode((string) $page->post_name))] ?? null;
            if ($to) {
                $this->point($full, $to, $report);
                $this->point('?page_id='.$page->ID, $to, $report);
            } else {
                $report->info('redirects', "برگه‌ی «{$full}» معادلی در page_redirects ندارد (می‌توانید در سئو ← ریدایرکت‌ها دستی بسازید).");
            }
        }
        foreach ($map as $from => $to) {
            $this->point($from, $to, $report);
        }
    }

    private function taxonomies(Report $report): void
    {
        $permalinks = (array) $this->wp->option('woocommerce_permalinks', []);

        // product categories: same path, maybe another base
        $categoryBase = $this->base($permalinks['category_base'] ?? null, 'product-category');
        $ours = trim((string) config('khaneh.urls.category_prefix'), '/');
        $paths = $this->termPaths('product_cat');
        foreach (Category::query()->whereNotNull('legacy_term_id')->get(['id', 'path', 'legacy_term_id']) as $category) {
            $to = Category::pathFor($category->path);
            if (UrlPath::normalize($categoryBase) !== UrlPath::normalize($ours) && isset($paths[$category->legacy_term_id])) {
                $this->point($categoryBase.'/'.$paths[$category->legacy_term_id], $to, $report);
            }
            $slug = $this->wp->table('terms')->where('term_id', $category->legacy_term_id)->value('slug');
            if ($slug) {
                $this->point('?product_cat='.rawurldecode($slug), $to, $report);
            }
        }

        // brands
        if ($taxonomy = $this->wp->firstTaxonomy((array) config('modules.wordpressimport.brand_taxonomies'))) {
            $brandBase = $this->base(match ($taxonomy) {
                'pwb-brand' => $this->wp->option('wc_pwb_admin_tab_slug'),
                'product_brand' => $permalinks['brand_base'] ?? $this->wp->option('woocommerce_product_brand_slug'),
                'pa_brand' => null,
                default => null,
            }, $taxonomy === 'pa_brand' ? 'pa_brand' : 'brand');
            $terms = $this->wp->terms($taxonomy);
            foreach (Brand::query()->whereNotNull('legacy_term_id')->get() as $brand) {
                if ($term = $terms->get($brand->legacy_term_id)) {
                    $this->point($brandBase.'/'.rawurldecode($term->slug), '/'.config('khaneh.urls.brand_prefix').'/'.$brand->slug, $report);
                }
            }
        }

        // product tags → search for the tag; blog taxonomies → blog
        $tagBase = $this->base($permalinks['tag_base'] ?? null, 'product-tag');
        foreach ($this->wp->terms('product_tag') as $term) {
            $this->point($tagBase.'/'.rawurldecode($term->slug), '/search?q='.rawurlencode(html_entity_decode($term->name)), $report);
        }
        $blog = '/'.config('khaneh.urls.blog_prefix');
        $categoryBase = $this->base($this->wp->option('category_base'), 'category');
        foreach ($this->termPaths('category') as $path) {
            $this->point($categoryBase.'/'.$path, $blog, $report);
        }
        $postTagBase = $this->base($this->wp->option('tag_base'), 'tag');
        foreach ($this->wp->terms('post_tag') as $term) {
            $this->point($postTagBase.'/'.rawurldecode($term->slug), $blog, $report);
        }
    }

    /** Every size WordPress made of an image → the one imported file. */
    private function images(Report $report): void
    {
        Media::query()->whereNotNull('legacy_url')->when($report->limit, fn ($q, $l) => $q->limit($l * 3))
            ->chunkById(300, function ($items) use ($report) {
                foreach ($items as $media) {
                    $relative = $this->media->relativeUploadPath((string) $media->legacy_url);
                    if (! $relative) {
                        continue;
                    }
                    $to = '/'.ltrim((string) parse_url(Storage::disk($media->disk)->url($media->path), PHP_URL_PATH), '/');
                    $this->point('wp-content/uploads/'.$relative, $to, $report);

                    $attachment = $this->wp->table('postmeta')->where('meta_key', '_wp_attached_file')->where('meta_value', $relative)->value('post_id');
                    $sizes = $attachment ? (array) (Source::unserialize($this->wp->table('postmeta')->where('post_id', $attachment)
                        ->where('meta_key', '_wp_attachment_metadata')->value('meta_value'))['sizes'] ?? []) : [];
                    $dir = str_contains($relative, '/') ? dirname($relative).'/' : '';
                    foreach ($sizes as $size) {
                        if (! empty($size['file'])) {
                            $this->point('wp-content/uploads/'.$dir.$size['file'], $to, $report);
                        }
                    }
                }
            });
    }

    /** term id => decoded slug path from the root ("parent/child") */
    private function termPaths(string $taxonomy): array
    {
        $terms = $this->wp->terms($taxonomy);
        $path = function (int $id, int $depth = 0) use (&$path, $terms): string {
            $term = $terms->get($id);

            return ! $term ? '' : ltrim(($term->parent && $depth < 10 ? $path((int) $term->parent, $depth + 1).'/' : '').rawurldecode((string) $term->slug), '/');
        };

        return $terms->keys()->mapWithKeys(fn ($id) => [$id => $path((int) $id)])->all();
    }
}
