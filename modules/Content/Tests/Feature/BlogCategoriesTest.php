<?php

/**
 * Magazine categories: /blog/category/{slug} lists its published articles; the article URL has no category.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Content\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Content\Models\Post;
use Modules\Content\Models\PostCategory;
use Tests\TestCase;

class BlogCategoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_pages_list_their_articles(): void
    {
        $guides = PostCategory::query()->where('slug', 'buying-guides')->sole();   // seeded by the migration
        $tips = PostCategory::query()->where('slug', 'tips')->sole();
        Post::create(['title' => 'راهنمای خرید سرویس غذاخوری', 'slug' => 'dinnerware-buying-guide', 'status' => Post::STATUS_PUBLISHED, 'published_at' => now()->subDay(), 'post_category_id' => $guides->id]);
        Post::create(['title' => 'شستن ظروف چینی', 'slug' => 'washing-porcelain', 'status' => Post::STATUS_PUBLISHED, 'published_at' => now()->subDay(), 'post_category_id' => $tips->id]);

        $this->get('/blog/category/buying-guides')->assertOk()
            ->assertSee('راهنمای خرید سرویس غذاخوری')->assertDontSee('شستن ظروف چینی')
            ->assertSee('<link rel="canonical" href="'.url('blog/category/buying-guides').'"', false);
        $this->get('/blog')->assertOk()->assertSee('راهنمای خرید سرویس غذاخوری')->assertSee('شستن ظروف چینی')
            ->assertSee(url('blog/category/tips'));                       // the category chips
        $this->get('/blog/dinnerware-buying-guide')->assertOk();            // no category in the article URL
        $this->get('/blog/category/no-such')->assertNotFound();
        $this->get('/sitemap-pages.xml')->assertSee(url('blog/category/buying-guides'));
    }
}
