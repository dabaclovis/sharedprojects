<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('site_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('owner_hash', 64)->index();
            $table->string('mode', 16);
            $table->string('status', 20)->default('running');
            $table->text('url');
            $table->json('data');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('site_reports'); }
};
