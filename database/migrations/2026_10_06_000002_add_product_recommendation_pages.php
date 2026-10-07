<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affiliate_products', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique();
            $table->string('seo_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->string('image_alt')->nullable();
            $table->text('best_for')->nullable();
            $table->text('pros')->nullable();
            $table->text('cons')->nullable();
        });
        DB::table('affiliate_products')->orderBy('id')->each(function ($product) {
            $base = Str::limit(Str::slug($product->title) ?: 'product', 190, '');
            $slug = $base;
            $suffix = 2;
            while (DB::table('affiliate_products')->where('slug', $slug)->exists()) {
                $slug = $base.'-'.$suffix++;
            }
            DB::table('affiliate_products')->where('id', $product->id)->update(['slug' => $slug]);
        });
    }

    public function down(): void
    {
        Schema::table('affiliate_products', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'seo_title', 'meta_description', 'image_alt', 'best_for', 'pros', 'cons']);
        });
    }
};
