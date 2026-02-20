<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Transaction History</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <form method="GET" class="bg-white p-4 rounded shadow grid grid-cols-1 md:grid-cols-5 gap-3">
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                    class="rounded border-gray-300">
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                    class="rounded border-gray-300">
                <select name="status" class="rounded border-gray-300">
                    <option value="">All statuses</option>
                    <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Pending</option>
                    <option value="success" @selected(($filters['status'] ?? '') === 'success')>Success</option>
                    <option value="failed" @selected(($filters['status'] ?? '') === 'failed')>Failed</option>
                </select>
                <input type="text" name="user" placeholder="User" value="{{ $filters['user'] ?? '' }}"
                    class="rounded border-gray-300">
                <button class="bg-indigo-600 text-white rounded px-4 py-2">Filter</button>
            </form>

            <div class="bg-white rounded shadow overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left">
                        <tr>
                            <th class="px-4 py-2">Date</th>
                            <th class="px-4 py-2">Invoice</th>
                            <th class="px-4 py-2">User</th>
                            <th class="px-4 py-2">Type</th>
                            <th class="px-4 py-2">Amount</th>
                            <th class="px-4 py-2">Reference</th>
                            <th class="px-4 py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $tx)
                            <tr class="border-t">
                                <td class="px-4 py-2">{{ $tx->created_at->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-2">{{ $tx->invoice_id ?: '-' }}</td>
                                <td class="px-4 py-2">{{ $tx->dhru_user }}</td>
                                <td class="px-4 py-2 uppercase">{{ $tx->type }}</td>
                                <td class="px-4 py-2">{{ number_format((float) $tx->amount, 2) }} {{ $tx->currency }}
                                </td>
                                <td class="px-4 py-2">{{ $tx->pawapay_reference }}</td>
                                <td class="px-4 py-2 uppercase">{{ $tx->status }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-4 text-center text-gray-500">No matching transactions.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>
                {{ $transactions->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
