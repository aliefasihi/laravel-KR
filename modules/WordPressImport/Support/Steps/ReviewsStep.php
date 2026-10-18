<?php

/**
 * Khaneh Romantic — WooCommerce product reviews (with the shop's reply)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Top-level comments on products become reviews (rating from comment meta; a comment without a
 * rating gets `review_default_rating`, or is skipped when that is null). A reply by staff becomes
 * the review's reply. Approved stays approved, held stays pending, spam/trash are skipped.
 */

namespace Modules\WordPressImport\Support\Steps;

use Modules\Catalog\Models\Product;
use Modules\Customer\Models\Customer;
use Modules\Reviews\Models\Review;
use Modules\WordPressImport\Support\Report;

class ReviewsStep extends Step
{
    public function key(): string
    {
        return 'reviews';
    }

    public function label(): string
    {
        return 'نظرهای محصولات';
    }

    public function requires(): array
    {
        return ['Reviews'];
    }

    private function query()
    {
        return $this->wp->table('comments as c')
            ->join('posts as p', 'p.ID', '=', 'c.comment_post_ID')
            ->where('p.post_type', 'product')
            ->where('c.comment_parent', 0)
            ->whereIn('c.comment_type', ['', 'review', 'comment'])
            ->whereIn('c.comment_approved', ['0', '1']);
    }

    public function sourceCount(): int
    {
        return $this->query()->count();
    }

    public function importedCount(): int
    {
        return Review::query()->whereNotNull('legacy_comment_id')->count();
    }

    public function run(Report $report): void
    {
        $default = config('modules.wordpressimport.review_default_rating', 5);
        $products = Product::withTrashed()->whereNotNull('legacy_post_id')->pluck('id', 'legacy_post_id')->all();
        $touched = [];

        $this->query()->orderBy('c.comment_ID')->select('c.*')
            ->when($report->limit, fn ($q, $l) => $q->limit($l))
            ->chunk(200, function ($comments) use ($report, $default, $products, &$touched) {
                $ids = $comments->pluck('comment_ID')->map(fn ($i) => (int) $i)->all();
                $meta = $this->wp->commentMeta($ids, ['rating', 'verified']);
                $replies = $this->wp->table('comments')->whereIn('comment_parent', $ids)->where('comment_approved', '1')
                    ->orderBy('comment_ID')->get()->groupBy('comment_parent');

                foreach ($comments as $c) {
                    $productId = $products[(int) $c->comment_post_ID] ?? null;
                    $rating = (int) ($meta[(int) $c->comment_ID]['rating'] ?? 0);
                    $rating = $rating >= 1 && $rating <= 5 ? $rating : $default;
                    if (! $productId || ! $rating || trim(strip_tags((string) $c->comment_content)) === '') {
                        $report->count('reviews', 'skipped');

                        continue;
                    }

                    $existing = Review::query()->where('legacy_comment_id', $c->comment_ID)->first();
                    $report->count('reviews', $existing ? 'updated' : 'created');
                    if ($report->dryRun) {
                        continue;
                    }

                    $customerId = (int) $c->user_id ? Customer::query()->where('legacy_wp_id', 'u'.$c->user_id)->value('id') : null;
                    // one review per customer and product here — later ones keep the text, unlinked
                    if ($customerId && Review::query()->where('product_id', $productId)->where('customer_id', $customerId)
                        ->when($existing, fn ($q) => $q->whereKeyNot($existing->id))->exists()) {
                        $customerId = null;
                    }
                    $reply = $replies->get($c->comment_ID)?->first();

                    $review = $existing ?? new Review;
                    $review->forceFill([
                        'product_id' => $productId,
                        'customer_id' => $customerId,
                        'name' => html_entity_decode((string) $c->comment_author) ?: 'مشتری',
                        'rating' => $rating,
                        'body' => trim(html_entity_decode(strip_tags((string) $c->comment_content))),
                        'is_verified_buyer' => ! empty($meta[(int) $c->comment_ID]['verified']),
                        'status' => $c->comment_approved === '1' ? 'approved' : 'pending',
                        'reply' => $reply ? trim(html_entity_decode(strip_tags((string) $reply->comment_content))) : $review->reply,
                        'replied_at' => $reply ? $this->date($reply->comment_date_gmt) : $review->replied_at,
                        'ip' => $c->comment_author_IP ?: null,
                        'legacy_comment_id' => (int) $c->comment_ID,
                        'created_at' => $this->date($c->comment_date_gmt) ?? now(),
                    ]);
                    Review::withoutEvents(fn () => $review->save());   // rating summary once per product below
                    $touched[$productId] = true;
                }
            });

        foreach (array_keys($touched) as $productId) {
            Review::refreshProduct($productId);
        }
    }
}
