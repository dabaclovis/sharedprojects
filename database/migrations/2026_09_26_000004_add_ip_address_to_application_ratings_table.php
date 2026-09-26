<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_ratings', function (Blueprint $table) {
            $table->ipAddress('ip_address')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('application_ratings', function (Blueprint $table) {
            $table->dropColumn('ip_address');
        });
    }
};
