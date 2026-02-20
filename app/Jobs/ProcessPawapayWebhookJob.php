<?php

namespace App\Jobs;

use App\Models\Payout;
use App\Services\PayoutService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class ProcessPawapayWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(private readonly array $payload) {}

    public function handle(PayoutService $payoutService): void
    {
        $reference = $this->extractReference($this->payload);

        if (! $reference) {
            Log::channel('payments')->warning('Webhook ignored: missing reference.', [
                'payload' => $this->payload,
            ]);

            return;
        }

        /** @var Payout|null $payout */
        $payout = Payout::query()
            ->where('pawapay_reference', $reference)
            ->first();

        if (! $payout) {
            Log::channel('payments')->warning('Webhook ignored: payout not found.', [
                'reference' => $reference,
                'payload' => $this->payload,
            ]);

            return;
        }

        $payoutService->processPayoutStatus($payout);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractReference(array $payload): ?string
    {
        return Arr::get($payload, 'payoutId')
            ?? Arr::get($payload, 'reference')
            ?? Arr::get($payload, 'transactionId')
            ?? Arr::get($payload, 'merchantReference');
    }
}
