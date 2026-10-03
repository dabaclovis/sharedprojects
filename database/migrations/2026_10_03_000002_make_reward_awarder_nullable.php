<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rewards', function (Blueprint $table) {
            $table->dropForeign(['awarded_by']);
            $table->foreignId('awarded_by')->nullable()->change();
            $table->foreign('awarded_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        DB::table('rewards')->whereNull('awarded_by')->update(['awarded_by' => DB::raw('user_id')]);

        Schema::table('rewards', function (Blueprint $table) {
            $table->dropForeign(['awarded_by']);
            $table->foreignId('awarded_by')->nullable(false)->change();
            $table->foreign('awarded_by')->references('id')->on('users')->restrictOnDelete();
        });
    }
};
