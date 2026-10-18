<?php

/**
 * Khaneh Romantic — Rewrites links and images inside imported content
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * href/src pointing at the old site (absolute or relative) become the new addresses: uploads →
 * the imported file (any WordPress size → the original), pages/products/posts → their redirect
 * target. Old srcset/sizes are removed (they list WordPress sizes). Run after «redirects».
 */

namespace Modules\WordPressImport\Support\Steps;

use App\Core\Support\StoreCache;
use App\Core\Support\UrlPath;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Content\Models\Post;
use Modules\Media\Models\Media;
use Modules\Seo\Models\Redirect;
use Modules\WordPressImport\Support\Report;

class LinksStep extends Step
{
    /** @var array<string, ?string> */
    private array $cache = [];

    public function key(): string
    {
        return 'links';
    }

    public function label(): string
    {
        return 'بازنویسی لینک‌ها و تصاویر داخل محتوا';
    }

    public function requires(): array
    {
        return ['Seo'];
    }

    public function sourceCount(): int
    {
        return Product::withTrashed()->whereNotNull('legacy_post_id')->whereNotNull('description')->count()
            + Post::query()->whereNotNull('legacy_post_id')->whereNotNull('body_html')->count();
    }

    public function importedCount(): int
    {
        return 0;
    }

    public function run(Report $report): void
    {
        $targets = [
            [Product::withTrashed()->whereNotNull('legacy_post_id'), ['description', 'short_description']],
            [Post::query()->whereNotNull('legacy_post_id'), ['body_html', 'excerpt']],
            [Category::query()->whereNotNull('legacy_term_id'), ['description']],
        ];

        foreach ($targets as [$query, $columns]) {
            $query->when($report->limit, fn ($q, $l) => $q->limit($l))->chunkById(100, function ($records) use ($columns, $report) {
                foreach ($records as $record) {
                    $changes = [];
                    foreach ($columns as $column) {
                        $html = (string) $record->getAttribute($column);
                        if ($html !== '' && ($new = $this->rewrite($html)) !== $html) {
                            $changes[$column] = $new;
                        }
                    }
                    $report->count('links', $changes ? 'updated' : 'skipped');
                    if ($changes && ! $report->dryRun) {
                        // query update: no slug/redirect side effects on the model
                        $record->newQuery()->whereKey($record->getKey())->update($changes);
                    }
                }
            });
        }
        StoreCache::flush();
    }

    public function rewrite(string $html): string
    {
        $html = preg_replace('/\s(srcset|sizes)\s*=\s*("[^"]*"|\'[^\']*\')/i', '', $html) ?? $html;

        return preg_replace_callback('/\b(href|src)\s*=\s*(["\'])(.*?)\2/i', function (array $m) {
            $url = html_entity_decode($m[3]);
            $new = $this->resolve($url);

            return $new === null ? $m[0] : $m[1].'='.$m[2].htmlspecialchars($new, ENT_QUOTES).$m[2];
        }, $html) ?? $html;
    }

    /** New address for an old one, or null to leave it. */
    public function resolve(string $url): ?string
    {
        if (array_key_exists($url, $this->cache)) {
            return $this->cache[$url];
        }
        $base = parse_url((string) config('modules.wordpressimport.legacy_base_url'), PHP_URL_HOST);
        $host = parse_url($url, PHP_URL_HOST);
        $internal = $host ? $base && preg_replace('/^www\./', '', strtolower($host)) === preg_replace('/^www\./', '', strtolower($base)) : str_starts_with($url, '/');
        if (! $internal || str_starts_with($url, '//') && ! $host) {
            return $this->cache[$url] = null;
        }

        $path = (string) (parse_url($url, PHP_URL_PATH) ?? '/');
        $query = parse_url($url, PHP_URL_QUERY);
        $fragment = parse_url($url, PHP_URL_FRAGMENT);
        $suffix = $fragment ? '#'.$fragment : '';

        if (str_contains($path, '/wp-content/uploads/')) {
            return $this->cache[$url] = $this->image($path);
        }

        $key = $path === '/' && $query ? '?'.explode('&', $query)[0] : $path;
        $to = Redirect::query()->where('from_hash', UrlPath::hash($key))->value('to_path');
        if ($to) {
            return $this->cache[$url] = $to.$suffix;
        }

        // still an old-site absolute link: make it relative so it works on the new domain
        return $this->cache[$url] = $host ? $path.($query ? '?'.$query : '').$suffix : null;
    }

    private function image(string $path): ?string
    {
        $relative = substr(rawurldecode($path), strpos(rawurldecode($path), '/wp-content/uploads/') + 20);
        $candidates = [$relative, preg_replace('/-\d+x\d+(?=\.[a-z0-9]+$)/i', '', $relative), preg_replace('/-scaled(?=\.[a-z0-9]+$)/i', '', $relative)];
        foreach (array_unique($candidates) as $candidate) {
            $media = Media::query()->where('legacy_url_hash', UrlPath::hash('wp-content/uploads/'.$candidate))->first();
            if ($media) {
                return '/'.ltrim((string) parse_url(Storage::disk($media->disk)->url($media->path), PHP_URL_PATH), '/');
            }
        }
        $to = Redirect::query()->where('from_hash', UrlPath::hash('wp-content/uploads/'.$relative))->value('to_path');

        return $to;   // not imported: left pointing at the old file
    }
}
