<?php

namespace App\Http\Middleware;

use App\Models\Post;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResolveArticleSlug
{
    public function handle(Request $request, Closure $next)
    {
        $slug = $request->route('slug');
        if (! Post::published()->where('slug', $slug)->exists()) {
            $id = DB::table('post_slug_aliases')->where('slug', $slug)->value('post_id');
            $post = Post::published()->findOrFail($id);

            return redirect()->route('pages.postshow', $post->slug, 301);
        }

        return $next($request);
    }
}
