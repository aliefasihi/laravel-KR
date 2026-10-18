<?php

/**
 * Khaneh Romantic — Attributes module (ویژگی‌ها، گروه ویژگی، فیلترها، جدول مشخصات)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Attributes\Providers;

use App\Core\Modules\ModuleServiceProvider;
use Illuminate\Support\Facades\View;
use Modules\Attributes\Models\AttributeGroup;
use Modules\Attributes\Support\AttributeTemplate;
use Modules\Attributes\Support\ProductAttributeValues;
use Modules\Catalog\Models\Category;
use Modules\Search\Support\ProductDocuments;

class AttributesServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->app->scoped(AttributeTemplate::class);
        $this->app->scoped(ProductAttributeValues::class);
    }

    public function boot(): void
    {
        parent::boot();

        Category::resolveRelationUsing('attributeGroups', fn (Category $category) => $category
            ->belongsToMany(AttributeGroup::class, 'attribute_group_category', 'category_id', 'group_id'));

        // search documents carry the value ids (filters) and labels (searchable: «استیل» finds steel pots)
        ProductDocuments::extend('attributes', fn ($products) => $this->app->make(ProductAttributeValues::class)->documents($products));

        // product page: full specs table
        View::composer('catalog::product.show', function ($view) {
            $product = $view->getData()['product'] ?? null;
            if ($product) {
                $view->with('attributeSpecs', $this->app->make(ProductAttributeValues::class)->specs($product->loadMissing('categories')));
            }
        });
    }
}
