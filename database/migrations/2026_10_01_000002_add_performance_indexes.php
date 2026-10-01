<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->index(['author_id', 'deleted_at', 'status', 'updated_at'], 'posts_author_management_index');
        });
        Schema::table('affiliate_products', function (Blueprint $table) {
            $table->index(['user_id', 'deleted_at', 'status', 'updated_at'], 'products_user_management_index');
        });
        Schema::table('events', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'starts_at', 'ends_at'], 'events_user_schedule_index');
            $table->index(['status', 'starts_at'], 'events_admin_schedule_index');
        });
        Schema::table('service_orders', function (Blueprint $table) {
            $table->index(['service', 'id'], 'service_orders_listing_index');
        });
        Schema::table('quotes', function (Blueprint $table) {
            $table->index('created_at', 'quotes_created_at_index');
        });
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->index('created_at', 'contact_messages_created_at_index');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'users_status_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('posts', fn (Blueprint $table) => $table->dropIndex('posts_author_management_index'));
        Schema::table('affiliate_products', fn (Blueprint $table) => $table->dropIndex('products_user_management_index'));
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('events_user_schedule_index');
            $table->dropIndex('events_admin_schedule_index');
        });
        Schema::table('service_orders', fn (Blueprint $table) => $table->dropIndex('service_orders_listing_index'));
        Schema::table('quotes', fn (Blueprint $table) => $table->dropIndex('quotes_created_at_index'));
        Schema::table('contact_messages', fn (Blueprint $table) => $table->dropIndex('contact_messages_created_at_index'));
        Schema::table('users', fn (Blueprint $table) => $table->dropIndex('users_status_created_at_index'));
    }
};
