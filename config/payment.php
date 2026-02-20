<?php

return [
    'statuses' => [
        'pending' => 'pending',
        'success' => 'success',
        'failed' => 'failed',
    ],

    'pawapay' => [
        'payout_endpoint' => env('PAWAPAY_PAYOUT_ENDPOINT', '/v2/payouts'),
        'payout_status_endpoint' => env('PAWAPAY_PAYOUT_STATUS_ENDPOINT', '/v2/payouts/{reference}'),
        'wallet_balances_endpoint' => env('PAWAPAY_WALLET_BALANCES_ENDPOINT', '/v2/wallet-balances'),
        'default_provider' => env('PAWAPAY_DEFAULT_PROVIDER'),
        'default_country' => env('PAWAPAY_DEFAULT_COUNTRY', 'COD'),
    ],
];
