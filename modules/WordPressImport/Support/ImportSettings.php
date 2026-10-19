<?php

/**
 * Khaneh Romantic — Applies the import settings chosen on the admin page to this run
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * The steps read config('modules.wordpressimport.*'); a run overrides those values with what the
 * admin confirmed for the uploaded database (currency, brand taxonomy and slugs, status map…).
 */

namespace Modules\WordPressImport\Support;

use Illuminate\Support\Str;

final class ImportSettings
{
    /**
     * @param  array<string, mixed>  $settings
     * @param  array<string, mixed>  $detected  analyzer output (for brand names)
     */
    public static function apply(array $settings, array $detected = []): void
    {
        $key = 'modules.wordpressimport.';
        if (! empty($settings['legacy_base_url'])) {
            config([$key.'legacy_base_url' => rtrim((string) $settings['legacy_base_url'], '/')]);
        }
        if (! empty($settings['currency'])) {
            config([$key.'currency' => (string) $settings['currency']]);
        }
        if (array_key_exists('brand_taxonomy', $settings)) {
            $taxonomy = (string) $settings['brand_taxonomy'];
            config([$key.'brand_taxonomies' => $taxonomy === '' ? ['__none__'] : [$taxonomy]]);

            $brands = $detected['brands'][$taxonomy] ?? [];
            $slugs = (array) config($key.'brand_slugs', []);
            foreach ((array) ($settings['brand_slugs'] ?? []) as $termId => $slug) {
                $slug = strtolower(trim((string) preg_replace('/[^a-z0-9-]+/i', '-', (string) $slug), '-'));
                if ($slug !== '' && isset($brands[$termId])) {
                    $slugs[$brands[$termId]['name']] = ['slug' => $slug, 'name_en' => $slugs[$brands[$termId]['name']]['name_en'] ?? Str::headline($slug)];
                }
            }
            config([$key.'brand_slugs' => $slugs]);
        }
        if (! empty($settings['order_statuses'])) {
            config([$key.'order_statuses' => array_merge((array) config($key.'order_statuses', []), (array) $settings['order_statuses'])]);
        }
        if (isset($settings['unmanaged_stock'])) {
            config([$key.'unmanaged_stock' => max(0, (int) $settings['unmanaged_stock'])]);
        }
        if (array_key_exists('review_default_rating', $settings)) {
            $rating = $settings['review_default_rating'];
            config([$key.'review_default_rating' => $rating === null || $rating === '' ? null : max(1, min(5, (int) $rating))]);
        }
    }
}
