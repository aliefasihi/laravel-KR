<?php

/**
 * Khaneh Romantic — <x-placement key="home.hero" :context-id="$category->id" />
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Placement\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;
use Modules\Placement\Models\Placement;

class Slot extends Component
{
    public Collection $banners;

    public function __construct(public string $key, public ?int $contextId = null)
    {
        $this->banners = Placement::liveFor($key, $contextId);
    }

    public function shouldRender(): bool
    {
        return $this->banners->isNotEmpty();
    }

    public function render(): View
    {
        return view('placement::components.slot');
    }
}
