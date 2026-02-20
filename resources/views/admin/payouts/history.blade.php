<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Historique des paiements</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Filters --}}
            <form method="GET" action="{{ route('admin.payouts.history') }}"
                class="bg-white p-4 rounded shadow flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Statut</label>
                    <select name="status" class="rounded border-gray-300 text-sm">
                        <option value="">Tous</option>
                        <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>En attente</option>
                        <option value="success" @selected(($filters['status'] ?? '') === 'success')>Réussi</option>
                        <option value="failed" @selected(($filters['status'] ?? '') === 'failed')>Échoué</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Téléphone</label>
                    <input type="text" name="phone" value="{{ $filters['phone'] ?? '' }}"
                        class="rounded border-gray-300 text-sm" placeholder="e.g. 2437...">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Du</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                        class="rounded border-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Au</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                        class="rounded border-gray-300 text-sm">
                </div>
                <button type="submit" class="bg-indigo-600 text-white text-sm px-4 py-2 rounded">Filtrer</button>
                <a href="{{ route('admin.payouts.history') }}"
                    class="text-sm text-gray-500 hover:underline self-center">Réinitialiser</a>
            </form>

            {{-- Refresh all pending button --}}
            @if ($payouts->where('status', 'pending')->isNotEmpty())
                <div class="flex justify-end">
                    <button onclick="document.querySelectorAll('[data-poll-btn]').forEach(b => b.click())"
                        class="text-sm bg-amber-50 border border-amber-300 text-amber-700 hover:bg-amber-100 px-3 py-1.5 rounded flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Actualiser les paiements en attente
                    </button>
                </div>
            @endif

            {{-- Table --}}
            <div class="bg-white rounded shadow overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left">
                        <tr>
                            <th class="px-4 py-2">Date</th>
                            <th class="px-4 py-2">Téléphone</th>
                            <th class="px-4 py-2">Opérateur</th>
                            <th class="px-4 py-2">Montant</th>
                            <th class="px-4 py-2">Référence</th>
                            <th class="px-4 py-2">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payouts as $payout)
                            @php
                                $statusLabel = match ($payout->status) {
                                    'pending' => 'En attente',
                                    'success' => 'Réussi',
                                    'failed' => 'Échoué',
                                    default => $payout->status,
                                };
                            @endphp
                            <tr class="border-t hover:bg-gray-50" x-data="{
                                status: @js($payout->status),
                                label: @js($statusLabel),
                                loading: false,
                                async poll() {
                                    this.loading = true;
                                    try {
                                        const r = await fetch(@js(route('admin.payouts.poll', $payout)));
                                        const d = await r.json();
                                        if (!d.error) { this.status = d.status;
                                            this.label = d.label; }
                                    } catch (e) {} finally { this.loading = false; }
                                }
                            }">
                                <td class="px-4 py-2 text-gray-500">{{ $payout->created_at->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-2">{{ $payout->phone_number }}</td>
                                <td class="px-4 py-2">{{ $payout->provider ?: '-' }}</td>
                                <td class="px-4 py-2">{{ number_format((float) $payout->amount, 2) }}
                                    {{ $payout->currency }}</td>
                                <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ $payout->pawapay_reference }}
                                </td>
                                <td class="px-4 py-2">
                                    <span
                                        x-bind:class="{
                                            'px-2 py-0.5 rounded-full text-xs font-medium': true,
                                            'bg-amber-100 text-amber-700': status === 'pending',
                                            'bg-emerald-100 text-emerald-700': status === 'success',
                                            'bg-red-100 text-red-700': status === 'failed'
                                        }"
                                        x-text="label"></span>
                                    <button x-show="status === 'pending'" x-bind:disabled="loading"
                                        x-on:click="poll()" data-poll-btn title="Actualiser le statut"
                                        class="ml-1 text-gray-400 hover:text-indigo-600 disabled:opacity-40 align-middle">
                                        <svg x-show="!loading" xmlns="http://www.w3.org/2000/svg"
                                            class="h-3.5 w-3.5 inline" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        <svg x-show="loading" xmlns="http://www.w3.org/2000/svg"
                                            class="h-3.5 w-3.5 inline animate-spin" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-gray-500">Aucun paiement trouvé.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $payouts->links() }}

        </div>
    </div>
</x-app-layout>
