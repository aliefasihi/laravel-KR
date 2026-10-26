<?php

/**
 * Khaneh Romantic — Blog: /blog, /blog/category/{slug}, /blog/{slug}
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Content\Http\Controllers;

use Illuminate\Contracts\View\View;
use Modules\Content\Models\Post;
use Modules\Content\Models\PostCategory;

class PostController
{
    public function index(): View
    {
        return $this->listing(null);
    }

    public function category(string $slug): View
    {
        return $this->listing(PostCategory::query()->where('slug', $slug)->firstOrFail());
    }

    public function show(string $slug): View
    {
        $post = Post::query()->published()->where('slug', $slug)->with('category')->firstOrFail();

        return view('content::post.show', compact('post'));
    }

    /** Every design's blog index gets the same variables; a category page is the index narrowed down. */
    private function listing(?PostCategory $category): View
    {
        $store = config('khaneh.store.name');
        $posts = Post::query()->published()->when($category, fn ($q) => $q->where('post_category_id', $category->id))
            ->latest('published_at')->paginate(12);
        $url = $category ? $category->url() : route('content.blog');

        return view('content::post.index', [
            'posts' => $posts,
            'category' => $category,
            'postCategories' => PostCategory::query()->whereHas('posts', fn ($q) => $q->published())
                ->withCount(['posts' => fn ($q) => $q->published()])->orderBy('sort')->get(),
            'blogTitle' => $category ? ($category->meta_title ?: $category->name.' — مجله '.$store) : 'مجله '.$store,
            'blogDescription' => $category ? ($category->meta_description ?: ($category->description ?: $category->name.' در مجله '.$store))
                : 'راهنمای خرید، نگهداری و ایده‌های چیدمان لوازم آشپزخانه، سرو و پذیرایی و دکوراسیون خانه.',
            'blogUrl' => $url.($posts->currentPage() > 1 ? '?page='.$posts->currentPage() : ''),
            'blogCrumbs' => $category ? ['مجله' => route('content.blog'), $category->name => null] : ['مجله' => null],
        ]);
    }
}
