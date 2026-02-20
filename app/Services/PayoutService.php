<?php

namespace App\Services;

use App\Models\Payout;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PayoutService
{
    public function __construct(
        private readonly PawapayService $pawapayService,
    ) {}

    public function initiatePayout(
        string $phoneNumber,
        float $amount,
        string $currency,
        ?string $description = null,
        ?string $provider = null,
    ): Payout {
        return DB::transaction(function () use ($phoneNumber, $amount, $currency, $description, $provider): Payout {
            $payoutId = Str::uuid()->toString();
            $idempotencyKey = 'payout-' . $payoutId;

            $response = $this->pawapayService->createPayout([
                'payoutId' => $payoutId,
                'phone_number' => $phoneNumber,
                'provider' => $provider,
                'amount' => $amount,
                'currency' => strtoupper($currency),
                'description' => $description,
                'client_reference_id' => $idempotencyKey,
            ], $idempotencyKey);

            $reference = (string) Arr::get(
                $response,
                'payoutId',
                Arr::get($response, 'reference', Arr::get($response, 'transactionId', $payoutId)),
            );

            $payout = Payout::query()->create([
                'payout_id' => $payoutId,
                'amount' => $amount,
                'currency' => strtoupper($currency),
                'phone_number' => $phoneNumber,
                'provider' => $provider,
                'description' => $description,
                'status' => Payout::STATUS_PENDING,
                'pawapay_reference' => $reference,
                'raw_response' => [
                    'request' => [
                        'phone_number' => $phoneNumber,
                        'provider' => $provider,
                        'description' => $description,
                    ],
                    'pawapay' => $response,
                ],
            ]);

            Log::channel('payments')->info('Payout initiated.', [
                'payout_id' => $payout->id,
                'pawapay_reference' => $reference,
                'amount' => $amount,
                'currency' => $currency,
            ]);

            return $payout;
        });
    }

    public function processPayoutStatus(Payout $payout): Payout
    {
        $statusPayload = $this->pawapayService->getPayoutStatus($payout->pawapay_reference);

        // PawaPay wraps the payout object inside "data"; top-level "status" is FOUND/NOT_FOUND
        $payoutStatus = (string) Arr::get($statusPayload, 'data.status', Arr::get($statusPayload, 'status', ''));
        $normalizedStatus = $this->normalizeExternalStatus($payoutStatus);

        return DB::transaction(function () use ($payout, $statusPayload, $normalizedStatus): Payout {
            /** @var Payout $locked */
            $locked = Payout::query()->lockForUpdate()->findOrFail($payout->id);

            if ($locked->status === Payout::STATUS_SUCCESS || $locked->status === Payout::STATUS_FAILED) {
                return $locked;
            }

            $locked->update([
                'status' => $normalizedStatus,
                'raw_response' => array_merge($locked->raw_response ?? [], [
                    'pawapay_status' => $statusPayload,
                ]),
            ]);

            Log::channel('payments')->info('Payout status updated.', [
                'payout_id' => $locked->id,
                'status' => $normalizedStatus,
            ]);

            return $locked->fresh();
        });
    }

    private function normalizeExternalStatus(string $status): string
    {
        return match (strtoupper($status)) {
            'COMPLETED', 'SUCCESS', 'SUCCEEDED' => Payout::STATUS_SUCCESS,
            'FAILED', 'CANCELLED', 'EXPIRED', 'REJECTED' => Payout::STATUS_FAILED,
            default => Payout::STATUS_PENDING,
        };
    }
}
