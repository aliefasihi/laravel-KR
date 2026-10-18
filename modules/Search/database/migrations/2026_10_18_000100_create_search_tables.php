<?php

/**
 * Khaneh Romantic — Search: database-engine text index + search term log
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Normalized words per product (name, brand fa/en, SKU, category names). Always kept up to
        // date, also when Meilisearch/Elasticsearch is the main engine — it is the fallback.
        Schema::create('product_search_texts', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->primary();
            $table->text('text');
            $table->timestamp('updated_at')->nullable();
        });

        // What shoppers search for: one row per normalized term.
        Schema::create('search_terms', function (Blueprint $table) {
            $table->id();
            $table->string('term', 191)->unique();
            $table->unsignedInteger('searches')->default(0);
            $table->unsignedInteger('last_results')->default(0);
            $table->unsignedInteger('zero_results')->default(0);   // how many of the searches found nothing
            $table->timestamp('last_searched_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_terms');
        Schema::dropIfExists('product_search_texts');
    }
};
