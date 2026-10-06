<?php

namespace App\Http\Controllers;

use App\Enums\PostCategory;
use App\Models\Post;

class SitemapController extends Controller
{
    public function __invoke()
    {
        $urls = collect(array_keys(config('seo.routes')))
            ->filter(fn ($name) => str_starts_with($name, 'pages.'))
            ->map(fn ($name) => ['loc' => route($name)]);
        foreach (PostCategory::cases() as $category) {
            $urls->push(['loc' => route('pages.article-category', strtolower($category->value))]);
        }
        foreach (Post::published()->select(['id', 'slug', 'updated_at'])->cursor() as $post) {
            $urls->push(['loc' => route('pages.postshow', $post->slug), 'lastmod' => $post->updated_at?->toAtomString()]);
        }

        return response()->view('sitemap', compact('urls'))->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
