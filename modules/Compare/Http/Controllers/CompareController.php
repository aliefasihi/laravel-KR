<?php

/**
 * Khaneh Romantic — Up to 4 products side by side: price, stock, rating and every spec row
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * The list is kept in the visitor's browser (localStorage «kr-compare»), so the page needs no
 * session and can be shared as a link. Rows: brand, code, category, then the attribute specs of all
 * the products (union, in the order they first appear). «فقط تفاوت‌ها» hides rows where all agree.
 */

namespace Modules\Compare\Http\Controllers;

use App\Core\Modules\Modules;
use App\Core\Support\PersianText;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Attributes\Support\ProductAttributeValues;
use Modules\Catalog\Models\Product;

class CompareController
{
    public const MAX = 4;

    public function __invoke(Request $request): View
    {
        $ids = collect(explode(',', (string) $request->query('ids')))
            ->map(fn ($id) => (int) PersianText::toLatinDigits(trim($id)))->filter()->unique()->take(self::MAX)->values();

        $products = Product::query()->active()->whereKey($ids)
            ->with(['brand', 'media', 'variants', 'categories'])->get()
            ->sortBy(fn (Product $p) => $ids->search($p->id))->values();

        $rows = [];
        foreach ($products as $i => $product) {
            $specs = array_filter([
                'برند' => $product->brand?->name_fa,
                'کد کالا' => $product->sku,
                'دسته‌بندی' => $product->categories->first()?->name,
            ]);
            if (Modules::enabled('Attributes')) {
                foreach (app(ProductAttributeValues::class)->specs($product) as $spec) {
                    $specs[$spec['name']] = $spec['value'];
                }
            }
            foreach ($specs as $name => $value) {
                $rows[$name][$i] = $value;
            }
        }
        // a row is «same» when every product has it with the same value
        $same = array_map(fn (array $cells) => count($cells) === $products->count() && count(array_unique($cells)) === 1, $rows);

        return view('compare::compare', ['products' => $products, 'rows' => $rows, 'same' => $same, 'max' => self::MAX]);
    }
}
