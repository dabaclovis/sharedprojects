<?php

namespace App\Http\Controllers;

use App\Models\ServiceOrder;

class BusinessReportController extends Controller
{
    public function __invoke(ServiceOrder $order)
    {
        abort_unless($order->service === 'website-audit' && $order->status === 'completed' && $order->payment_status === 'paid', 404);

        return response()->view('reports.business-audit', compact('order'))
            ->header('Cache-Control', 'private, no-store')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
