<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Soldes des portefeuilles PawaPay</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if ($error)
                <div class="bg-red-50 text-red-700 p-3 rounded">{{ $error }}</div>
            @endif

            @if (empty($balances))
                <div class="bg-white p-6 rounded shadow text-gray-500">Aucune donnée de solde disponible.</div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    @foreach ($balances as $wallet)
                        <div class="bg-white rounded shadow space-y-2 border-t-4 border-indigo-500 px-6 py-6">
                            <p class="text-xs text-gray-400 uppercase tracking-widest font-medium">
                                {{ $wallet['country'] ?? '-' }}</p>
                            <p class="text-xl font-bold text-gray-800 font-mono">
                                {{ number_format((float) ($wallet['balance'] ?? 0), 2) }}
                            </p>
                            <p class="text-xs text-gray-500 font-semibold">{{ $wallet['currency'] ?? '' }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
