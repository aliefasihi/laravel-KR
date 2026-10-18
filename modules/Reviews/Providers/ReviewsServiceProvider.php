<?php

/**
 * Khaneh Romantic — Reviews module
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Adds the «نظرات» tab and section to product pages, stars on product cards (products.rating_*),
 * aggregateRating in the product schema, and moderation in the admin.
 */

namespace Modules\Reviews\Providers;

use App\Core\Modules\ModuleServiceProvider;
use App\Core\Settings\Settings;
use App\Core\Settings\SettingsSection;
use App\Core\Support\StorefrontHooks;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Toggle;
use Livewire\Livewire;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Support\ProductSchema;
use Modules\Reviews\Livewire\ReviewForm;
use Modules\Reviews\Models\Review;

class ReviewsServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        Livewire::component('reviews.form', ReviewForm::class);
        StorefrontHooks::add('product.tabs', 'reviews::partials.tab');
        StorefrontHooks::add('product.sections', 'reviews::partials.section');

        ProductSchema::extend('reviews', function (Product $product, array $schema) {
            if (! $product->rating_count) {
                return $schema;
            }
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (float) $product->rating_avg,
                'reviewCount' => (int) $product->rating_count,
                'bestRating' => 5,
                'worstRating' => 1,
            ];
            $schema['review'] = Review::query()->approved()->where('product_id', $product->id)->latest('id')->limit(5)->get()
                ->map(fn (Review $r) => [
                    '@type' => 'Review',
                    'author' => ['@type' => 'Person', 'name' => $r->name],
                    'datePublished' => $r->created_at->toDateString(),
                    'reviewRating' => ['@type' => 'Rating', 'ratingValue' => $r->rating, 'bestRating' => 5],
                    'reviewBody' => $r->body,
                ])->all();

            return $schema;
        });

        Settings::section(new SettingsSection(
            key: 'reviews',
            title: 'نظرات',
            icon: 'heroicon-o-star',
            sort: 40,
            defaults: ['auto_approve' => 'none', 'require_purchase' => false],
            schema: fn () => [
                Radio::make('auto_approve')->label('انتشار نظر')->options([
                    'none' => 'همه‌ی نظرها پس از تأیید در پنل',
                    'verified' => 'نظر خریداران این محصول خودکار؛ بقیه پس از تأیید',
                    'all' => 'همه خودکار (توصیه نمی‌شود)',
                ])->required(),
                Toggle::make('require_purchase')->label('فقط خریداران محصول بتوانند نظر بنویسند'),
            ],
        ));
    }
}
