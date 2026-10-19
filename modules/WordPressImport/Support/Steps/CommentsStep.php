<?php

/**
 * Khaneh Romantic — WordPress blog comments (with replies; staff replies become shop replies)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\WordPressImport\Support\Steps;

use Modules\Comments\Models\Comment;
use Modules\Content\Models\Post;
use Modules\Customer\Models\Customer;
use Modules\Customer\Support\Mobile;
use Modules\WordPressImport\Support\Report;

class CommentsStep extends Step
{
    public function key(): string
    {
        return 'comments';
    }

    public function label(): string
    {
        return 'نظرهای مقاله‌ها';
    }

    public function requires(): array
    {
        return ['Comments'];
    }

    private function query()
    {
        return $this->wp->table('comments as c')
            ->join('posts as p', 'p.ID', '=', 'c.comment_post_ID')
            ->where('p.post_type', 'post')
            ->whereIn('c.comment_type', ['', 'comment'])
            ->whereIn('c.comment_approved', ['0', '1']);
    }

    public function sourceCount(): int
    {
        return $this->query()->count();
    }

    public function importedCount(): int
    {
        return Comment::query()->whereNotNull('legacy_comment_id')->count();
    }

    public function run(Report $report): void
    {
        $posts = Post::query()->whereNotNull('legacy_post_id')->pluck('id', 'legacy_post_id')->all();
        $prefix = (string) config('database.connections.wordpress.prefix');
        $staff = $this->wp->table('usermeta')->where('meta_key', $prefix.'capabilities')
            ->where(fn ($q) => $q->where('meta_value', 'like', '%administrator%')->orWhere('meta_value', 'like', '%shop_manager%')->orWhere('meta_value', 'like', '%editor%'))
            ->pluck('user_id')->map(fn ($id) => (int) $id)->all();
        $map = Comment::query()->whereNotNull('legacy_comment_id')->pluck('id', 'legacy_comment_id')->all();

        $this->query()->select('c.*')->orderBy('c.comment_ID')
            ->when($report->limit, fn ($q, $l) => $q->limit($l))
            ->chunk(300, function ($rows) use ($report, $posts, $staff, &$map) {
                foreach ($rows as $c) {
                    $postId = $posts[(int) $c->comment_post_ID] ?? null;
                    $body = trim(html_entity_decode(strip_tags((string) $c->comment_content)));
                    if (! $postId || $body === '') {
                        $report->count('comments', 'skipped');

                        continue;
                    }
                    $existing = $map[(int) $c->comment_ID] ?? null;
                    $report->count('comments', $existing ? 'updated' : 'created');
                    if ($report->dryRun) {
                        continue;
                    }

                    $isStaff = (int) $c->user_id && in_array((int) $c->user_id, $staff, true);
                    $parent = (int) $c->comment_parent ? ($map[(int) $c->comment_parent] ?? null) : null;
                    if ($parent && Comment::query()->whereKey($parent)->value('parent_id')) {
                        $parent = Comment::query()->whereKey($parent)->value('parent_id');   // one level of replies here
                    }
                    $comment = $existing ? Comment::find($existing) : new Comment;
                    $comment->forceFill([
                        'commentable_type' => (new Post)->getMorphClass(),
                        'commentable_id' => $postId,
                        'parent_id' => $parent,
                        'customer_id' => (int) $c->user_id && ! $isStaff ? Customer::query()->where('legacy_wp_id', 'u'.$c->user_id)->value('id') : null,
                        'name' => mb_substr(html_entity_decode((string) $c->comment_author) ?: 'کاربر', 0, 80),
                        'mobile' => Mobile::normalize((string) $c->comment_author_email) ?: null,
                        'email' => filter_var($c->comment_author_email, FILTER_VALIDATE_EMAIL) ? mb_strtolower($c->comment_author_email) : null,
                        'body' => $body,
                        'status' => $c->comment_approved === '1' ? 'approved' : 'pending',
                        'is_staff' => $isStaff,
                        'ip' => $c->comment_author_IP ?: null,
                        'legacy_comment_id' => (int) $c->comment_ID,
                        'created_at' => $this->date($c->comment_date_gmt) ?? now(),
                        'updated_at' => now(),
                    ])->save();
                    $map[(int) $c->comment_ID] = $comment->id;
                }
            });
    }
}
