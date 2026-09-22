<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Existing accounts can set a username when their profile is updated.
            $table->string('username')->nullable()->unique();
            $table->enum('role', ['user', 'admin'])->default('user');
            $table->enum('status', ['active', 'inactive'])->default('active');
            // Add a foreign key when the corresponding people table exists.
            $table->unsignedBigInteger('person_id')->nullable()->unique();

            $table->index(['role', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role', 'status']);
            $table->dropUnique(['username']);
            $table->dropUnique(['person_id']);
            $table->dropColumn(['username', 'role', 'status', 'person_id']);
        });
    }
};
