<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\Payments\IntegrationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePayoutRequest;
use App\Models\Payout;
use App\Services\PayoutService;
use App\Services\PawapayService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class PayoutController extends Controller
{
    public function create(PawapayService $pawapayService): View
    {
        $providers = [];

        try {
            $availability = $pawapayService->getProviderAvailability('COD', 'PAYOUT');
            $countryData = Arr::first($availability, fn($c) => ($c['country'] ?? '') === 'COD') ?? [];

            foreach ($countryData['providers'] ?? [] as $p) {
                $operationTypes = $p['operationTypes'] ?? [];

                // Dictionary format: {"PAYOUT": "OPERATIONAL"}
                if (isset($operationTypes['PAYOUT'])) {
                    $status = $operationTypes['PAYOUT'];
                } else {
                    // Array format: [{"operationType": "PAYOUT", "status": "OPERATIONAL"}, ...]
                    $match = collect($operationTypes)->first(fn($op) => ($op['operationType'] ?? '') === 'PAYOUT');
                    $status = $match['status'] ?? 'UNKNOWN';
                }

                $providers[] = [
                    'provider' => $p['provider'],
                    'status'   => $status,
                ];
            }
        } catch (IntegrationException) {
            // proceed with empty list; user can type manually
        }

        return view('admin.payouts.create', ['providers' => $providers]);
    }

    public function store(StorePayoutRequest $request, PayoutService $payoutService): RedirectResponse
    {
        try {
            $payoutService->initiatePayout(
                phoneNumber: (string) $request->string('phone_number'),
                amount: (float) $request->input('amount'),
                currency: (string) $request->string('currency'),
                description: $request->input('description'),
                provider: $request->input('provider'),
            );
        } catch (IntegrationException) {
            return back()->withInput()->with('error', 'Unable to create payout request.');
        }

        return redirect()->route('admin.payouts.history')->with('success', 'Payout request submitted.');
    }

    public function history(Request $request): View
    {
        $payouts = Payout::query()
            ->when($request->filled('status'), fn(Builder $query) => $query->where('status', $request->string('status')))
            ->when($request->filled('phone'), fn(Builder $query) => $query->where('phone_number', 'like', '%' . $request->string('phone') . '%'))
            ->when($request->filled('date_from'), fn(Builder $query) => $query->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn(Builder $query) => $query->whereDate('created_at', '<=', $request->date('date_to')))
            ->latest()
            ->paginate(20)
            ->appends($request->query());

        return view('admin.payouts.history', [
            'payouts' => $payouts,
            'filters' => $request->only(['status', 'phone', 'date_from', 'date_to']),
        ]);
    }

    public function pollStatus(Payout $payout, PayoutService $payoutService): JsonResponse
    {
        try {
            $refreshed = $payoutService->processPayoutStatus($payout);
        } catch (\Throwable) {
            return response()->json(['error' => 'Impossible de vérifier le statut.'], 500);
        }

        $labels = [
            Payout::STATUS_PENDING => 'En attente',
            Payout::STATUS_SUCCESS => 'Réussi',
            Payout::STATUS_FAILED  => 'Échoué',
        ];

        return response()->json([
            'id'     => $refreshed->id,
            'status' => $refreshed->status,
            'label'  => $labels[$refreshed->status] ?? $refreshed->status,
        ]);
    }
}
