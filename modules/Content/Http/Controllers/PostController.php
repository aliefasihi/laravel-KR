<?php

/**
 * Khaneh Romantic — Blog
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Content\Http\Controllers;

use Illuminate\Contracts\View\View;
use Modules\Content\Models\Post;

class PostController
{
    public function index(): View
    {
        $posts = Post::query()->published()->latest('published_at')->paginate(12);

        return view('content::post.index', compact('posts'));
    }

    public function show(string $slug): View
    {
        $post = Post::query()->published()->where('slug', $slug)->firstOrFail();

        return view('content::post.show', compact('post'));
    }
}
