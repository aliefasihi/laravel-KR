<?php

/**
 * Khaneh Romantic — XML sitemaps and robots.txt
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 *   /sitemap.xml                 index → submit this one in Search Console / Bing Webmaster
 *   /sitemap-pages.xml           home, shop, blog, about, contact, privacy, returns
 *   /sitemap-categories.xml      active product categories
 *   /sitemap-brands.xml          brands with products
 *   /sitemap-products-{n}.xml    published products, 1000 per file, with their main image
 *   /sitemap-posts.xml           published articles
 *   /sitemap_index.xml           (the old WordPress address) → 301 to /sitemap.xml
 *   /robots.txt                  keeps crawlers out of carts, accounts and endless filter combinations
 * Every file is cached and rebuilt after any catalog/content change (StoreCache).
 */

namespace Modules\Seo\Http\Controllers;

use App\Core\Modules\Modules;
use App\Core\Support\StoreCache;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Collections\Models\Collection;
use Modules\Content\Models\Post;
use Modules\Content\Models\PostCategory;

class SitemapController
{
    public const PER_FILE = 1000;

    public function index(): Response
    {
        return $this->xml(StoreCache::remember('seo.sitemap.index', function () {
            $files = ['pages', 'categories', 'brands'];
            $pages = max(1, (int) ceil(Product::query()->active()->count() / self::PER_FILE));
            foreach (range(1, $pages) as $n) {
                $files[] = 'products-'.$n;
            }
            if (Modules::enabled('Content')) {
                $files[] = 'posts';
            }
            $lastmod = optional(Product::query()->active()->max('updated_at'), fn ($d) => Carbon::parse($d)->toAtomString());

            $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
            foreach ($files as $file) {
                $xml .= '  <sitemap><loc>'.e(url('sitemap-'.$file.'.xml')).'</loc>'.($lastmod ? '<lastmod>'.$lastmod.'</lastmod>' : '').'</sitemap>'."\n";
            }

            return $xml.'</sitemapindex>';
        }, maxTtl: 21600));
    }

    public function products(int $page): Response
    {
        return $this->part('products', $page);
    }

    public function part(string $type, ?int $page = null): Response
    {
        $xml = StoreCache::remember('seo.sitemap.'.$type.'.'.($page ?? 0), fn () => match ($type) {
            'pages' => $this->urlset($this->pages()),
            'categories' => $this->urlset(Category::query()->visible()->orderBy('id')->get()
                ->map(fn (Category $c) => ['loc' => $c->url(), 'lastmod' => $c->updated_at])),
            'brands' => $this->urlset(Brand::query()->whereHas('products', fn ($q) => $q->active())->orderBy('id')->get()
                ->map(fn (Brand $b) => ['loc' => $b->url(), 'lastmod' => $b->updated_at])),
            'products' => $this->urlset(Product::query()->active()->with('media')->orderBy('id')
                ->forPage(max(1, (int) $page), self::PER_FILE)->get()
                ->map(fn (Product $p) => ['loc' => $p->url(), 'lastmod' => $p->updated_at,
                    'image' => $p->media->first()?->absoluteUrlFor(1200), 'title' => $p->name])),
            'posts' => Modules::enabled('Content') ? $this->urlset(Post::query()->published()->orderBy('id')->get()
                ->map(fn (Post $p) => ['loc' => $p->url(), 'lastmod' => $p->updated_at])) : null,
            default => null,
        }, maxTtl: 21600);

        abort_if($xml === null || ($type === 'products' && $page > 1 && ! str_contains($xml, '<url>')), 404);

        return $this->xml($xml);
    }

    public function legacy()
    {
        return redirect('/sitemap.xml', 301);
    }

    public function robots(): Response
    {
        $rules = [
            'User-agent: *',
            // private and endless pages
            'Disallow: /admin', 'Disallow: /livewire', 'Disallow: /cart', 'Disallow: /checkout', 'Disallow: /account',
            'Disallow: /orders/', 'Disallow: /payment/', 'Disallow: /search/suggest', 'Disallow: /recently-viewed',
            // filter combinations (pages ?page=2… stay open)
            'Disallow: /*?*sort=', 'Disallow: /*?*brand', 'Disallow: /*?*attr', 'Disallow: /*?*min=', 'Disallow: /*?*max=',
            'Disallow: /*?*in_stock=', 'Disallow: /*?*sale=', 'Disallow: /*?*utm_', 'Disallow: /*?*occasion=', 'Disallow: /*?*design=',
            '',
            'Sitemap: '.url('sitemap.xml'),
        ];

        return response(implode("\n", $rules)."\n", 200, ['Content-Type' => 'text/plain; charset=utf-8', 'Cache-Control' => 'public, max-age=3600']);
    }

    /** @return list<array{loc: string}> */
    private function pages(): array
    {
        $routes = ['home', 'catalog.shop', 'content.blog', 'pages.about', 'pages.contact', 'pages.privacy', 'pages.returns'];

        $pages = array_values(array_filter(array_map(fn ($name) => app('router')->has($name) ? ['loc' => route($name)] : null, $routes)));

        // magazine categories that have a published article
        if (class_exists(PostCategory::class) && app('router')->has('content.blog.category')) {
            foreach (PostCategory::query()->whereHas('posts', fn ($q) => $q->published())->orderBy('sort')->get() as $category) {
                $pages[] = ['loc' => $category->url(), 'lastmod' => $category->updated_at];
            }
        }

        if (app('router')->has('collections.show')) {
            foreach (Collection::query()->visible()->orderBy('sort')->get() as $collection) {
                $pages[] = ['loc' => $collection->url(), 'lastmod' => $collection->updated_at];
            }
        }

        return $pages;
    }

    private function urlset(iterable $urls): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'."\n";
        foreach ($urls as $url) {
            $xml .= '  <url><loc>'.e($url['loc']).'</loc>'
                .(! empty($url['lastmod']) ? '<lastmod>'.Carbon::parse($url['lastmod'])->toAtomString().'</lastmod>' : '')
                .(! empty($url['image']) ? '<image:image><image:loc>'.e($url['image']).'</image:loc>'.(! empty($url['title']) ? '<image:title>'.e($url['title']).'</image:title>' : '').'</image:image>' : '')
                .'</url>'."\n";
        }

        return $xml.'</urlset>';
    }

    private function xml(string $xml): Response
    {
        return response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8', 'Cache-Control' => 'public, max-age=3600', 'X-Robots-Tag' => 'noindex']);
    }
}
