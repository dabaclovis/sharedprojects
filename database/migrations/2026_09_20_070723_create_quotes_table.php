<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->string('author')->nullable();
            $table->string('source')->nullable();
            $table->string('tags')->nullable();
            $table->string('category')->nullable();
            $table->string('language')->nullable();
            $table->string('licon')->nullable();
            $table->string('ricon')->nullable();
            $table->string('ipaddr')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
