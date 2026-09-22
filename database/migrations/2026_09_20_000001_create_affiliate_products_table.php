<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('merchant', 120);
            $table->string('category', 100)->nullable();
            $table->text('affiliate_url');
            $table->text('image_url')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('status', 20)->default('draft');
            $table->unsignedBigInteger('clicks')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'deleted_at', 'created_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_products');
    }
};
