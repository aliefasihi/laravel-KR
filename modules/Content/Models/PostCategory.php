<?php

/**
 * Khaneh Romantic — Magazine category (URL: /blog/category/{slug}); a post's address doesn't include it
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Content\Models;

use App\Core\Concerns\FlushesStoreCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostCategory extends Model
{
    use FlushesStoreCache;

    protected $guarded = ['id'];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function url(): string
    {
        return route('content.blog.category', $this->slug);
    }
}
