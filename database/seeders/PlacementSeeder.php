<?php

/**
 * Khaneh Romantic — Standard banner slots used by the storefront templates
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Placement\Models\Placement;

class PlacementSeeder extends Seeder
{
    public function run(): void
    {
        $slots = [
            ['key' => 'home.hero', 'name' => 'صفحه اصلی — بنر بالا', 'context_type' => null, 'desktop_width' => 1440, 'desktop_height' => 520, 'mobile_width' => 750, 'mobile_height' => 900, 'max_items' => 1],
            ['key' => 'category.header', 'name' => 'بالای صفحه دسته‌بندی', 'context_type' => 'category', 'desktop_width' => 1440, 'desktop_height' => 300, 'mobile_width' => 750, 'mobile_height' => 420, 'max_items' => 1],
            ['key' => 'product.sidebar', 'name' => 'صفحه محصول — کنار خرید', 'context_type' => null, 'desktop_width' => 600, 'desktop_height' => 300, 'mobile_width' => null, 'mobile_height' => null, 'max_items' => 1],
        ];

        foreach ($slots as $slot) {
            Placement::query()->updateOrCreate(['key' => $slot['key']], $slot + ['is_active' => true]);
        }
    }
}
