<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $stats = [
            'total_volume' => Payout::query()->where('status', Payout::STATUS_SUCCESS)->sum('amount'),
            'successful_payouts' => Payout::query()->where('status', Payout::STATUS_SUCCESS)->count(),
            'failed_payouts' => Payout::query()->where('status', Payout::STATUS_FAILED)->count(),
            'pending_payouts' => Payout::query()->where('status', Payout::STATUS_PENDING)->count(),
        ];

        $recentPayouts = Payout::query()->latest()->limit(10)->get();

        return view('admin.dashboard', [
            'stats' => $stats,
            'recentPayouts' => $recentPayouts,
        ]);
    }
}
