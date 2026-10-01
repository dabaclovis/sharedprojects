<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reward_funds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('balance_cents')->default(0);
            $table->unsignedInteger('quote_reward_cents')->default(2500);
            $table->unsignedInteger('post_reward_cents')->default(100);
            $table->unsignedSmallInteger('post_min_words')->default(350);
            $table->unsignedSmallInteger('post_max_words')->default(600);
            $table->timestamps();
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('awarded_by')->constrained('users')->restrictOnDelete();
            $table->string('content_type', 20);
            $table->unsignedBigInteger('content_id');
            $table->unsignedInteger('amount_cents');
            $table->timestamps();
            $table->unique(['content_type', 'content_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rewards');
        Schema::table('quotes', fn (Blueprint $table) => $table->dropConstrainedForeignId('user_id'));
        Schema::dropIfExists('reward_funds');
    }
};
