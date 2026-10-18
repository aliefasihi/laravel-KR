<?php

/**
 * Khaneh Romantic — Brings a WordPress attachment (or an uploads URL in content) into Media
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * The file is copied from WP_UPLOADS_PATH (the old wp-content/uploads folder on this server) when
 * set, otherwise downloaded from the live site. Saved under media/imported/YYYY/MM with an ASCII
 * name; `php artisan filemanager:sync --move` later files product images into their folders.
 * Matched by the original URL, so nothing is downloaded twice.
 */

namespace Modules\WordPressImport\Support;

use App\Core\Support\UrlPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Media\Models\Media;
use Throwable;

class MediaImporter
{
    /** @var array<int, ?int> attachment id => media id */
    private array $byAttachment = [];

    public function __construct(private Source $wp) {}

    public function baseUrl(): string
    {
        return rtrim((string) config('modules.wordpressimport.legacy_base_url'), '/');
    }

    public function uploadsUrl(): string
    {
        return $this->baseUrl().'/wp-content/uploads';
    }

    /** @param  list<int>  $ids */
    public function warm(array $ids): void
    {
        $ids = array_values(array_diff(array_unique(array_filter($ids)), array_keys($this->byAttachment)));
        if ($ids === []) {
            return;
        }
        $meta = $this->wp->postMeta($ids, ['_wp_attached_file', '_wp_attachment_image_alt']);
        $posts = $this->wp->table('posts')->whereIn('ID', $ids)->get(['ID', 'post_title', 'guid'])->keyBy('ID');
        foreach ($ids as $id) {
            $this->pending[$id] = ['meta' => $meta[$id] ?? [], 'post' => $posts[$id] ?? null];
        }
    }

    /** @var array<int, array{meta: array, post: ?object}> */
    private array $pending = [];

    public function fromAttachment(int $attachmentId, Report $report, ?string $alt = null): ?Media
    {
        if (array_key_exists($attachmentId, $this->byAttachment)) {
            return $this->byAttachment[$attachmentId] ? Media::find($this->byAttachment[$attachmentId]) : null;
        }
        $this->warm([$attachmentId]);
        $info = $this->pending[$attachmentId] ?? null;
        $file = $info['meta']['_wp_attached_file'] ?? null;
        $url = $file ? $this->uploadsUrl().'/'.ltrim($file, '/') : ($info['post']->guid ?? null);
        if (! $url) {
            $report->warn('media', "پیوست {$attachmentId} پیدا نشد.");

            return $this->remember($attachmentId, null);
        }

        $media = $this->fromUrl($url, $report, $alt ?: ($info['meta']['_wp_attachment_image_alt'] ?? null) ?: null, $info['post']->post_title ?? null);

        return $this->remember($attachmentId, $media);
    }

    public function fromUrl(string $url, Report $report, ?string $alt = null, ?string $title = null): ?Media
    {
        $hash = UrlPath::hash($url);
        if ($existing = Media::query()->where('legacy_url_hash', $hash)->first()) {
            return $existing;
        }
        if ($report->dryRun) {
            $report->count('media', 'created');

            return null;
        }

        $relative = $this->relativeUploadPath($url);
        $bytes = $this->read($relative, $url, $report);
        if ($bytes === null) {
            $report->count('media', 'failed');

            return null;
        }

        $info = pathinfo($relative ?? parse_url($url, PHP_URL_PATH) ?? 'file');
        $ext = strtolower($info['extension'] ?? 'jpg');
        $dir = 'media/imported/'.(preg_match('#^(\d{4}/\d{2})/#', (string) $relative, $m) ? $m[1] : now()->format('Y/m'));
        $name = Str::slug($info['filename'] ?? '') ?: 'image';
        $name = Str::limit($name, 80, '').'-'.substr($hash, 0, 6).'.'.$ext;
        Storage::disk('public')->put($dir.'/'.$name, $bytes);

        $report->count('media', 'created');

        return Media::create([
            'disk' => 'public',
            'path' => $dir.'/'.$name,
            'alt' => $alt,
            'title' => $title,
            'legacy_url' => $url,
            'legacy_url_hash' => $hash,
        ]);
    }

    /** "https://site/wp-content/uploads/2024/05/x.jpg" → "2024/05/x.jpg" (decoded) */
    public function relativeUploadPath(string $url): ?string
    {
        $path = rawurldecode((string) parse_url($url, PHP_URL_PATH));
        $pos = strpos($path, '/wp-content/uploads/');

        return $pos === false ? null : substr($path, $pos + strlen('/wp-content/uploads/'));
    }

    private function read(?string $relative, string $url, Report $report): ?string
    {
        $local = config('modules.wordpressimport.uploads_path');
        if ($local && $relative && is_file($file = rtrim($local, '/').'/'.$relative)) {
            return file_get_contents($file) ?: null;
        }

        try {
            $response = Http::timeout(40)->retry(2, 500, throw: false)->get($this->encodeUrl($url));
            if ($response->successful() && $response->body() !== '') {
                return $response->body();
            }
            $report->warn('media', 'دانلود نشد ('.$response->status().'): '.$url);
        } catch (Throwable $e) {
            $report->warn('media', 'دانلود نشد: '.$url.' — '.$e->getMessage());
        }

        return null;
    }

    /** Persian file names must be percent-encoded for HTTP. */
    private function encodeUrl(string $url): string
    {
        $parts = parse_url($url);
        $path = implode('/', array_map(fn ($s) => rawurlencode(rawurldecode($s)), explode('/', $parts['path'] ?? '')));

        return ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '').$path;
    }

    private function remember(int $attachmentId, ?Media $media): ?Media
    {
        $this->byAttachment[$attachmentId] = $media?->id;

        return $media;
    }
}
