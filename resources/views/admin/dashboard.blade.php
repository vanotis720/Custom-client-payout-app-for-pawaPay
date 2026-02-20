<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tableau de bord</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded shadow">
                    <p class="text-sm text-gray-500">Volume total</p>
                    <p class="text-xl font-semibold">{{ number_format((float) $stats['total_volume'], 2) }}</p>
                </div>
                <div class="bg-white p-4 rounded shadow">
                    <p class="text-sm text-gray-500">Réussis</p>
                    <p class="text-xl font-semibold text-emerald-700">{{ $stats['successful_payouts'] }}</p>
                </div>
                <div class="bg-white p-4 rounded shadow">
                    <p class="text-sm text-gray-500">Échoués</p>
                    <p class="text-xl font-semibold text-red-700">{{ $stats['failed_payouts'] }}</p>
                </div>
                <div class="bg-white p-4 rounded shadow">
                    <p class="text-sm text-gray-500">En attente</p>
                    <p class="text-xl font-semibold text-amber-600">{{ $stats['pending_payouts'] }}</p>
                </div>
            </div>

            <div class="bg-white rounded shadow overflow-hidden">
                <div class="p-4 border-b font-semibold flex justify-between items-center">
                    <span>Paiements récents</span>
                    <a href="{{ route('admin.payouts.history') }}" class="text-sm text-blue-600 hover:underline">Voir
                        tout</a>
                </div>
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left">
                        <tr>
                            <th class="px-4 py-2">Téléphone</th>
                            <th class="px-4 py-2">Opérateur</th>
                            <th class="px-4 py-2">Montant</th>
                            <th class="px-4 py-2">Statut</th>
                            <th class="px-4 py-2">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentPayouts as $payout)
                            <tr class="border-t">
                                <td class="px-4 py-2">{{ $payout->phone_number }}</td>
                                <td class="px-4 py-2">{{ $payout->provider ?: '-' }}</td>
                                <td class="px-4 py-2">{{ number_format((float) $payout->amount, 2) }}
                                    {{ $payout->currency }}</td>
                                <td class="px-4 py-2">
                                    {{ match ($payout->status) {'pending' => 'En attente','success' => 'Réussi','failed' => 'Échoué',default => $payout->status} }}
                                </td>
                                <td class="px-4 py-2 text-gray-500">{{ $payout->created_at->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-4 text-center text-gray-500">Aucun paiement trouvé.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
