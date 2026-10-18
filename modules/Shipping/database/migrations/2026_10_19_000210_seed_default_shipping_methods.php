<?php

/**
 * Khaneh Romantic — starter shipping methods (edit or switch off in the admin)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('shipping_methods')->exists()) {
            return;
        }

        $now = now();
        DB::table('shipping_methods')->insert([
            ['code' => 'post', 'name' => 'پست پیشتاز', 'description' => '۳ تا ۵ روز کاری، سراسر ایران', 'cost' => 900_000,
                'province_costs' => json_encode(['تهران' => 600_000], JSON_UNESCAPED_UNICODE), 'cities' => null, 'provinces' => null,
                'free_over' => 50_000_000, 'pay_on_delivery' => false, 'sort' => 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'courier', 'name' => 'پیک موتوری (تهران)', 'description' => 'همان روز یا روز بعد', 'cost' => 1_200_000,
                'province_costs' => null, 'cities' => json_encode(['تهران'], JSON_UNESCAPED_UNICODE), 'provinces' => json_encode(['تهران'], JSON_UNESCAPED_UNICODE),
                'free_over' => null, 'pay_on_delivery' => false, 'sort' => 2, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'tipax', 'name' => 'تیپاکس (پس‌کرایه)', 'description' => 'هزینه‌ی ارسال هنگام تحویل به مأمور تیپاکس پرداخت می‌شود', 'cost' => 0,
                'province_costs' => null, 'cities' => null, 'provinces' => null,
                'free_over' => null, 'pay_on_delivery' => true, 'sort' => 3, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        DB::table('shipping_methods')->whereIn('code', ['post', 'courier', 'tipax'])->delete();
    }
};
