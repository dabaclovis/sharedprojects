<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('seo_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->string('image_alt')->nullable();
            $table->string('target_keyword')->nullable();
            $table->json('tags')->nullable();
        });
        Schema::create('post_slug_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->string('slug')->unique();
        });
        DB::table('posts')->orderBy('id')->each(function ($post) {
            $base = preg_replace('/-[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}$/i', '', $post->slug);
            if (! $base || $base === $post->slug) {
                return;
            }
            $slug = $base;
            $suffix = 2;
            while (DB::table('posts')->where('slug', $slug)->exists() || DB::table('post_slug_aliases')->where('slug', $slug)->exists()) {
                $slug = $base.'-'.$suffix++;
            }
            DB::table('post_slug_aliases')->insert(['post_id' => $post->id, 'slug' => $post->slug]);
            DB::table('posts')->where('id', $post->id)->update(['slug' => $slug]);
        });
    }

    public function down(): void
    {
        // Retain the cleaned URLs on rollback; the original posts remain intact.
        Schema::dropIfExists('post_slug_aliases');
        Schema::table('posts', fn (Blueprint $table) => $table->dropColumn(['seo_title', 'meta_description', 'image_alt', 'target_keyword', 'tags']));
    }
};
