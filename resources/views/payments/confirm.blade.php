<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Payment</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-lg bg-white rounded-xl shadow p-6">
        <h1 class="text-2xl font-semibold mb-4">Confirm payment on your phone</h1>

        @if (session('info'))
            <div class="bg-blue-50 text-blue-700 p-3 rounded mb-4">{{ session('info') }}</div>
        @endif

        <dl class="space-y-2 mb-6 text-sm">
            <div class="flex justify-between">
                <dt class="font-medium">Invoice</dt>
                <dd>{{ $transaction->invoice_id }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="font-medium">User</dt>
                <dd>{{ $transaction->dhru_user }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="font-medium">Amount</dt>
                <dd>{{ number_format((float) $transaction->amount, 2) }} {{ $transaction->currency }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="font-medium">Reference</dt>
                <dd>{{ $transaction->pawapay_reference }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="font-medium">Status</dt>
                <dd class="uppercase">{{ $transaction->status }}</dd>
            </div>
        </dl>

        <form method="POST" action="{{ route('payment.check-status') }}">
            @csrf
            <input type="hidden" name="transaction_id" value="{{ $transaction->id }}">
            <button type="submit" class="w-full bg-indigo-600 text-white rounded px-4 py-2 hover:bg-indigo-700">
                I have confirmed on phone — Check status
            </button>
        </form>
    </div>
</body>

</html>
