<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_dashboard_links', function (Blueprint $table) {
            $table->string('url', 2048)->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('admin_dashboard_links')->whereNull('url')->update(['url' => '']);

        Schema::table('admin_dashboard_links', function (Blueprint $table) {
            $table->string('url', 2048)->nullable(false)->change();
        });
    }
};
