<?php

/**
 * Khaneh Romantic — «لیست قیمت همکار»: every model with its regular and wholesale price, and a
 * quick order (type the quantities, add them all to the cart at once). Approved customers only.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Wholesale\Livewire;

use App\Core\Support\PersianText;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Checkout\Support\Cart;
use Modules\Wholesale\Support\Wholesale;

class PriceList extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'cat', except: 0)]
    public int $category = 0;

    #[Url(as: 'stock', except: false)]
    public bool $inStock = false;

    /** @var array<int|string, int|string> variant id => quantity typed in */
    public array $qty = [];

    public string $message = '';

    public function mount(Wholesale $wholesale): void
    {
        abort_unless($wholesale->current(), 403);
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['search', 'category', 'inStock'], true)) {
            $this->resetPage();
        }
    }

    /** Adds every typed quantity to the cart. */
    public function addAll(Cart $cart, Wholesale $wholesale): void
    {
        abort_unless($wholesale->current(), 403);
        $added = 0;
        foreach ($this->qty as $id => $qty) {
            $qty = (int) PersianText::toLatinDigits((string) $qty);
            if ($qty > 0 && $cart->add((int) $id, $qty) > 0) {
                $added++;
            }
        }
        $this->qty = [];
        $this->message = $added
            ? PersianText::toPersianDigits($added).' مدل به سبد خرید اضافه شد.'
            : 'تعداد هیچ مدلی را وارد نکرده‌اید (یا ناموجود است).';
        $this->dispatch('cart-updated');
    }

    public function render(Wholesale $wholesale)
    {
        $categories = $this->category ? (Category::query()->find($this->category)?->descendantIds() ?? []) : [];

        $products = Product::query()->active()
            ->whereHas('variants', fn (Builder $q) => $q->where('is_active', true)->when($this->inStock, fn ($q) => $q->where('stock', '>', 0)))
            ->when($categories, fn (Builder $q) => $q->whereHas('categories', fn ($c) => $c->whereIn('categories.id', $categories)))
            ->when(trim($this->search) !== '', function (Builder $q) {
                $term = '%'.PersianText::normalize(trim($this->search)).'%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('sku', 'like', $term));
            })
            ->with(['media', 'brand', 'variants' => fn ($q) => $q->where('is_active', true)->orderBy('price')])
            ->orderBy('name')
            ->paginate(20);

        return view('wholesale::livewire.price-list', [
            'products' => $products,
            'wholesale' => $wholesale,
            'categoryOptions' => Category::query()->whereNull('parent_id')->orderBy('sort')->with('children:id,parent_id,name')->get(['id', 'name']),
            'minimum' => Wholesale::minimum(),
        ]);
    }
}
