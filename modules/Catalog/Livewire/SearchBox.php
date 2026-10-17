<?php

/**
 * Khaneh Romantic — Header search with instant suggestions
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Catalog\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\Catalog\Support\ProductSearch;

class SearchBox extends Component
{
    public string $q = '';

    #[Computed]
    public function results(): Collection
    {
        return mb_strlen(trim($this->q)) < 2
            ? collect()
            : app(ProductSearch::class)->query($this->q)->limit(6)->get();
    }

    public function render(): View
    {
        return view('catalog::livewire.search-box');
    }
}
