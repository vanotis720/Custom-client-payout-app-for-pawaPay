<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\Payments\IntegrationException;
use App\Http\Controllers\Controller;
use App\Services\PawapayService;
use Illuminate\View\View;

class BalanceController extends Controller
{
    public function __invoke(PawapayService $pawapayService): View
    {
        $balances = [];
        $error = null;

        try {
            $response = $pawapayService->getWalletBalances();
            $balances = $response['balances'] ?? $response;
        } catch (IntegrationException $e) {
            $error = 'Unable to retrieve wallet balances from pawaPay.';
        }

        return view('admin.balances.index', [
            'balances' => $balances,
            'error' => $error,
        ]);
    }
}
