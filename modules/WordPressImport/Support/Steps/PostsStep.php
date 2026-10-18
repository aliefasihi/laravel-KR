<?php

/**
 * Khaneh Romantic — Blog posts (ASCII addresses /blog/{slug}; old ones redirected)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\WordPressImport\Support\Steps;

use Illuminate\Support\Str;
use Modules\Content\Models\Post;
use Modules\WordPressImport\Support\MediaImporter;
use Modules\WordPressImport\Support\Report;
use Modules\WordPressImport\Support\Source;

class PostsStep extends Step
{
    public function __construct(Source $wp, private MediaImporter $media)
    {
        parent::__construct($wp);
    }

    public function key(): string
    {
        return 'posts';
    }

    public function label(): string
    {
        return 'مقاله‌ها';
    }

    public function sourceCount(): int
    {
        return $this->wp->table('posts')->where('post_type', 'post')->whereIn('post_status', ['publish', 'draft', 'future'])->count();
    }

    public function importedCount(): int
    {
        return Post::query()->whereNotNull('legacy_post_id')->count();
    }

    public function run(Report $report): void
    {
        $query = $this->wp->table('posts')->where('post_type', 'post')->whereIn('post_status', ['publish', 'draft', 'future'])->orderBy('ID');
        if ($report->limit) {
            $query->limit($report->limit);
        }

        $query->chunk(100, function ($posts) use ($report) {
            $meta = $this->wp->postMeta($posts->pluck('ID')->map(fn ($id) => (int) $id)->all(),
                ['_thumbnail_id', 'rank_math_title', 'rank_math_description', '_yoast_wpseo_title', '_yoast_wpseo_metadesc']);
            foreach ($posts as $wp) {
                $m = $meta[(int) $wp->ID] ?? [];
                $existing = Post::query()->where('legacy_post_id', $wp->ID)->first();
                $report->count('posts', $existing ? 'updated' : 'created');
                if ($report->dryRun) {
                    continue;
                }

                $post = $existing ?? new Post;
                $post->fill([
                    'slug' => $post->slug ?: $this->slug($wp),
                    'title' => html_entity_decode($wp->post_title),
                    'excerpt' => $wp->post_excerpt ?: null,
                    'body_html' => $wp->post_content,
                    'status' => in_array($wp->post_status, ['publish', 'future'], true) ? 'published' : 'draft',
                    'published_at' => $this->date($wp->post_date_gmt) ?? $this->date($wp->post_date),
                    'meta_title' => ($t = $m['rank_math_title'] ?? $m['_yoast_wpseo_title'] ?? null) && ! str_contains($t, '%') ? $t : null,
                    'meta_description' => ($d = $m['rank_math_description'] ?? $m['_yoast_wpseo_metadesc'] ?? null) && ! str_contains($d, '%') ? Str::limit($d, 490, '') : null,
                ]);
                $post->legacy_post_id = (int) $wp->ID;
                $post->save();

                if (! empty($m['_thumbnail_id']) && $post->media()->doesntExist()) {
                    if ($image = $this->media->fromAttachment((int) $m['_thumbnail_id'], $report, $post->title)) {
                        $post->media()->attach($image->id, ['collection' => 'cover', 'position' => 0]);
                    }
                }
            }
        });
    }

    /** English slug kept; Persian ones become a readable ASCII form (or post-{id}). */
    private function slug(object $wp): string
    {
        $decoded = rawurldecode((string) $wp->post_name);
        $base = preg_match('/^[a-z0-9-]+$/i', $decoded) ? strtolower($decoded) : (Str::slug($decoded) ?: Str::slug($wp->post_title));
        $base = Str::limit($base ?: 'post-'.$wp->ID, 100, '');
        $slug = $base;
        for ($i = 2; Post::query()->where('slug', $slug)->exists(); $i++) {
            $slug = $base.'-'.$i;
        }

        return $slug;
    }
}
