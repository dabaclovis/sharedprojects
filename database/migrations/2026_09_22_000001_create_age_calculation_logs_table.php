<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('age_calculation_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('age_years');
            $table->unsignedTinyInteger('age_months');
            $table->unsignedTinyInteger('age_days');
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('calculated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('age_calculation_logs');
    }
};
