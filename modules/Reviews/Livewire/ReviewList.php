<?php

/**
 * Khaneh Romantic — Product reviews list: sort, «helpful» votes, load more
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Reviews\Livewire;

use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Reviews\Models\Review;

class ReviewList extends Component
{
    public const SORTS = ['newest' => 'جدیدترین', 'helpful' => 'مفیدترین', 'highest' => 'بیشترین امتیاز', 'lowest' => 'کمترین امتیاز'];

    #[Locked]
    public int $productId;

    public string $sort = 'newest';

    public int $perPage = 6;

    public function mount(int $productId): void
    {
        $this->productId = $productId;
    }

    public function updatedSort(): void
    {
        $this->sort = array_key_exists($this->sort, self::SORTS) ? $this->sort : 'newest';
        $this->perPage = 6;
    }

    public function loadMore(): void
    {
        $this->perPage += 10;
    }

    public function helpful(int $id): void
    {
        $voted = (array) session('reviews.helpful', []);
        if (in_array($id, $voted, true)) {
            return;
        }
        Review::query()->approved()->where('product_id', $this->productId)->whereKey($id)->increment('helpful');
        session()->put('reviews.helpful', [...$voted, $id]);
    }

    public function render()
    {
        $query = Review::query()->approved()->where('product_id', $this->productId);
        $total = (clone $query)->count();
        match ($this->sort) {
            'helpful' => $query->orderByDesc('helpful')->latest('id'),
            'highest' => $query->orderByDesc('rating')->latest('id'),
            'lowest' => $query->orderBy('rating')->latest('id'),
            default => $query->latest('id'),
        };

        return view('reviews::livewire.review-list', [
            'reviews' => $query->limit($this->perPage)->get(),
            'total' => $total,
            'voted' => (array) session('reviews.helpful', []),
        ]);
    }
}
