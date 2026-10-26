<?php

/**
 * Khaneh Romantic — magazine categories (/blog/category/{slug})
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 80)->unique();
            $table->text('description')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->integer('sort')->default(0);
            $table->timestamps();
        });
        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('post_category_id')->nullable()->after('id')->constrained('post_categories')->nullOnDelete();
        });

        $now = now();
        DB::table('post_categories')->insert([
            ['name' => 'راهنمای خرید', 'slug' => 'buying-guides', 'description' => 'پیش از خرید ظروف و لوازم خانه، این راهنماها را بخوانید.', 'sort' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'ایده‌های چیدمان و دکوراسیون', 'slug' => 'decor-ideas', 'description' => 'ایده‌هایی برای چیدمان میز، آشپزخانه و خانه‌ای شیک و گرم.', 'sort' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'ترفندها و نگهداری', 'slug' => 'tips', 'description' => 'شستشو، نگهداری و ترفندهای کاربردی برای ظروف و لوازم خانه.', 'sort' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::table('posts', fn (Blueprint $table) => $table->dropConstrainedForeignId('post_category_id'));
        Schema::dropIfExists('post_categories');
    }
};
