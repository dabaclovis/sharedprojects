<?php

namespace App\Http\Controllers;

use App\Models\SiteReport;

class SiteReportController extends Controller
{
    public function __invoke(SiteReport $report)
    {
        abort_unless(hash_equals($report->owner_hash, hash('sha256', session()->getId())), 404);

        return response()->view('reports.site-download', ['report' => $report, 'onlineReport' => true])
            ->header('Cache-Control', 'private, no-store')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
