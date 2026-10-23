<?php

/**
 * Product questions and the shop's answers.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('mobile', 15)->nullable();            // never shown; for «جواب داده شد» SMS
            $table->text('body');
            $table->text('answer')->nullable();
            $table->foreignId('answered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('answered_at')->nullable();
            $table->string('status', 12)->default('pending');   // pending | published | rejected
            $table->boolean('notify')->default(true);            // SMS the asker when answered
            $table->unsignedInteger('helpful')->default(0);
            $table->string('ip', 45)->nullable();
            $table->timestamps();
            $table->index(['product_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_questions');
    }
};
