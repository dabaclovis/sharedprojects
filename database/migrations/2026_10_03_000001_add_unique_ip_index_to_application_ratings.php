<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateIps = DB::table('application_ratings')
            ->select('ip_address')
            ->whereNotNull('ip_address')
            ->groupBy('ip_address')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('ip_address');

        foreach ($duplicateIps as $ipAddress) {
            $duplicateIds = DB::table('application_ratings')
                ->where('ip_address', $ipAddress)
                ->orderBy('id')
                ->skip(1)
                ->pluck('id');

            DB::table('application_ratings')->whereIn('id', $duplicateIds)->update(['ip_address' => null]);
        }

        Schema::table('application_ratings', function (Blueprint $table) {
            $table->unique('ip_address', 'application_ratings_ip_address_unique');
        });
    }

    public function down(): void
    {
        Schema::table('application_ratings', function (Blueprint $table) {
            $table->dropUnique('application_ratings_ip_address_unique');
        });
    }
};
