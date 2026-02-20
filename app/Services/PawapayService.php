<?php

namespace App\Services;

use App\Exceptions\Payments\IntegrationException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PawapayService
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createPayout(array $payload, string $idempotencyKey): array
    {
        $requestPayload = $this->buildPayoutPayload($payload, $idempotencyKey);

        $response = $this->request($idempotencyKey)
            ->post(config('payment.pawapay.payout_endpoint'), $requestPayload);

        $this->logInteraction('create_payout', $response, $requestPayload);

        if ($response->failed()) {
            throw new IntegrationException('pawaPay payout creation failed.');
        }

        return $this->responseAsArray($response);
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayoutStatus(string $reference): array
    {
        $endpoint = str_replace('{reference}', $reference, (string) config('payment.pawapay.payout_status_endpoint'));
        $response = $this->request()->get($endpoint);

        $this->logInteraction('payout_status', $response, ['reference' => $reference]);

        if ($response->failed()) {
            throw new IntegrationException('pawaPay payout status check failed.');
        }

        return $this->responseAsArray($response);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getProviderAvailability(string $country, string $operationType = 'PAYOUT'): array
    {
        $response = $this->request()->get('/v2/availability', [
            'country' => strtoupper($country),
            'operationType' => strtoupper($operationType),
        ]);

        $this->logInteraction('provider_availability', $response, ['country' => $country, 'operationType' => $operationType]);

        if ($response->failed()) {
            throw new IntegrationException('pawaPay provider availability check failed.');
        }

        $data = $response->json();

        return is_array($data) ? $data : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getWalletBalances(): array
    {
        $response = $this->request()->get((string) config('payment.pawapay.wallet_balances_endpoint', '/v2/wallet-balances'));

        $this->logInteraction('wallet_balances', $response, []);

        if ($response->failed()) {
            throw new IntegrationException('pawaPay wallet balance check failed.');
        }

        return $this->responseAsArray($response);
    }

    private function request(?string $idempotencyKey = null)
    {
        $request = Http::baseUrl((string) config('services.pawapay.base_url'))
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.pawapay.timeout', 20))
            ->withToken((string) config('services.pawapay.api_key'))
            ->retry(2, 200);

        if ($idempotencyKey) {
            $request = $request->withHeaders(['Idempotency-Key' => $idempotencyKey]);
        }

        return $request;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function buildPayoutPayload(array $payload, string $idempotencyKey): array
    {
        $payoutId = (string) ($payload['payoutId'] ?? $payload['payout_id'] ?? Str::uuid()->toString());
        $currency = strtoupper((string) ($payload['currency'] ?? 'USD'));
        $phoneNumber = (string) ($payload['phone_number'] ?? $payload['recipient_phone'] ?? '');
        $provider = $this->resolveProvider((string) ($payload['provider'] ?? ''), $currency);

        if (blank($phoneNumber)) {
            throw new IntegrationException('pawaPay payout requires a recipient phone number.');
        }

        return array_filter([
            'payoutId' => $payoutId,
            'amount' => $this->formatAmount((float) ($payload['amount'] ?? 0)),
            'currency' => $currency,
            'recipient' => [
                'type' => 'MMO',
                'accountDetails' => [
                    'phoneNumber' => $this->normalizePhoneNumber($phoneNumber),
                    'provider' => $provider,
                ],
            ],
            'clientReferenceId' => (string) ($payload['clientReferenceId'] ?? $payload['client_reference_id'] ?? $idempotencyKey),
            'metadata' => Arr::get($payload, 'metadata', [
                [
                    'source' => 'payout-api',
                    'description' => (string) ($payload['description'] ?? ''),
                ],
            ]),
        ], fn($value) => ! is_null($value) && $value !== '');
    }

    private function formatAmount(float $amount): string
    {
        if ($amount <= 0) {
            throw new IntegrationException('pawaPay amount must be greater than zero.');
        }

        return number_format($amount, 2, '.', '');
    }

    private function normalizePhoneNumber(string $phone): string
    {
        return preg_replace('/[\s\-\(\)\+]/', '', $phone) ?? $phone;
    }

    private function resolveProvider(string $provider, string $currency): string
    {
        if (filled($provider) && strtoupper($provider) === $provider && str_contains($provider, '_')) {
            return $provider;
        }

        $country = strtoupper((string) config('payment.pawapay.default_country', $this->mapCurrencyToCountry($currency)));

        if (filled($provider)) {
            return $this->mapProviderCode($provider, $country);
        }

        $defaultProvider = (string) config('payment.pawapay.default_provider');
        if (filled($defaultProvider)) {
            return $defaultProvider;
        }

        throw new IntegrationException('No pawaPay provider configured. Set PAWAPAY_DEFAULT_PROVIDER or provide provider per request.');
    }

    private function mapProviderCode(string $provider, string $country): string
    {
        $providerMap = [
            'COD' => ['orange_money' => 'ORANGE_COD', 'airtel_money' => 'AIRTEL_COD', 'mpesa' => 'VODACOM_COD', 'africell' => 'AFRICELL_COD'],
            'CMR' => ['orange_money' => 'ORANGE_CMR', 'mtn_money' => 'MTN_MOMO_CMR'],
            'CIV' => ['orange_money' => 'ORANGE_CIV', 'mtn_money' => 'MTN_MOMO_CIV', 'moov_money' => 'MOOV_CIV'],
            'GHA' => ['mtn_money' => 'MTN_MOMO_GHA', 'vodafone' => 'VODAFONE_GHA', 'airtel_tigo' => 'AIRTEL_TIGO_GHA'],
            'KEN' => ['mpesa' => 'MPESA_KE', 'airtel_money' => 'AIRTEL_KE'],
            'RWA' => ['mtn_money' => 'MTN_MOMO_RWA', 'airtel_money' => 'AIRTEL_RWA'],
            'TZA' => ['mpesa' => 'MPESA_TZA', 'airtel_money' => 'AIRTEL_TZA', 'tigo' => 'TIGO_TZA'],
            'UGA' => ['mtn_money' => 'MTN_MOMO_UGA', 'airtel_money' => 'AIRTEL_UGA'],
            'ZMB' => ['mtn_money' => 'MTN_MOMO_ZMB', 'airtel_money' => 'AIRTEL_ZMB'],
        ];

        $countryProviders = $providerMap[$country] ?? [];
        $key = strtolower($provider);

        return $countryProviders[$key] ?? strtoupper($provider);
    }

    private function mapCurrencyToCountry(string $currency): string
    {
        return [
            'CDF' => 'COD',
            'XOF' => 'CIV',
            'XAF' => 'CMR',
            'GHS' => 'GHA',
            'KES' => 'KEN',
            'RWF' => 'RWA',
            'TZS' => 'TZA',
            'UGX' => 'UGA',
            'ZMW' => 'ZMB',
            'USD' => 'COD',
        ][strtoupper($currency)] ?? 'COD';
    }

    /**
     * @return array<string, mixed>
     */
    private function responseAsArray(Response $response): array
    {
        $data = $response->json();

        if (! is_array($data)) {
            throw new IntegrationException('Unexpected pawaPay response format.');
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function logInteraction(string $action, Response $response, array $payload): void
    {
        Log::channel('payments')->info('pawaPay API interaction', [
            'action' => $action,
            'status' => $response->status(),
            'payload' => $payload,
            'response' => $response->json(),
        ]);
    }
}
