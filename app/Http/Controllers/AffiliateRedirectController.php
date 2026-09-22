<?php

namespace App\Http\Controllers;

use App\Models\AffiliateProduct;
use Illuminate\Http\RedirectResponse;

class AffiliateRedirectController extends Controller
{
    public function __invoke(int $product): RedirectResponse
    {
        $listing = AffiliateProduct::published()->findOrFail($product);
        abort_unless(filter_var($listing->affiliate_url, FILTER_VALIDATE_URL)
            && in_array(strtolower((string) parse_url($listing->affiliate_url, PHP_URL_SCHEME)), ['http', 'https'], true), 404);
        $listing->increment('clicks');

        return redirect()->away($listing->affiliate_url)->withHeaders(['Cache-Control' => 'no-store']);
    }
}
