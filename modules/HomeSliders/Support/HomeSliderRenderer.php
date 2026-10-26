<?php

/**
 * Khaneh Romantic — Renders the sliders of one home-page slot
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Called while the home body is built; the result lives in the cached home HTML, so a slider
 * costs nothing per visit. Any slider/product change flushes StoreCache.
 */

namespace Modules\HomeSliders\Support;

use App\Core\Support\StoreCache;
use App\Core\Support\StorefrontHooks;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Modules\Catalog\Models\ProductVariant;
use Modules\HomeSliders\Models\HomeSlider;

class HomeSliderRenderer
{
    /** @var Collection<string, Collection<int, HomeSlider>>|null */
    private $bySlot = null;

    private ?string $version = null;

    public function render(string $slot): HtmlString
    {
        // one query for all five slots of a home render; reloaded when anything changed since
        if ($this->bySlot === null || $this->version !== StoreCache::version()) {
            $this->version = StoreCache::version();
            $this->bySlot = HomeSlider::query()->active()->with(['category', 'brand'])
                ->orderBy('sort')->orderBy('id')->get()->groupBy('slot');
        }

        $html = '';
        $afterNewest = false;
        foreach ($this->bySlot->get($slot, collect()) as $slider) {
            $products = $slider->products();
            if ($products->count() < 2) {
                continue; // nothing worth sliding
            }
            $html .= view('homesliders::slider', [
                'slider' => $slider,
                'products' => $products,
                'dealsEnd' => $slider->style === 'band' ? $this->dealsEnd($products->modelKeys()) : null,
            ])->render();

            // other modules (Collections) sit right under the newest-products slider
            if ($slider->source === 'newest' && ! $afterNewest) {
                $html .= StorefrontHooks::render('home.after_newest')->toHtml();
                $afterNewest = true;
            }
        }

        // no newest slider here: the main slot still carries them
        if ($slot === 'after_categories' && ! $afterNewest && ! $this->bySlot->collapse()->contains(fn ($s) => $s->source === 'newest' && $s->slot !== $slot)) {
            $html .= StorefrontHooks::render('home.after_newest')->toHtml();
        }

        return new HtmlString($html);
    }

    /** Countdown target: the earliest sale end among the slider's products, otherwise midnight in Tehran. */
    private function dealsEnd(array $productIds): Carbon
    {
        $now = now();
        $end = ProductVariant::query()->whereIn('product_id', $productIds)->where('is_active', true)
            ->whereNotNull('sale_price')->where('sale_ends_at', '>', $now)->min('sale_ends_at');

        return $end ? Carbon::parse($end) : $now->copy()->setTimezone(config('khaneh.display_timezone'))->endOfDay();
    }
}
