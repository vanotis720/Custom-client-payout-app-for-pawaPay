<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nouveau paiement</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="bg-emerald-50 text-emerald-700 p-3 rounded">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="bg-red-50 text-red-700 p-3 rounded">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('admin.payouts.store') }}" class="bg-white p-6 rounded shadow space-y-4">
                @csrf

                <div>
                    <label class="block text-sm mb-1 font-medium">Numéro de téléphone</label>
                    <input type="text" name="phone_number" value="{{ old('phone_number') }}"
                        placeholder="ex. 243812345678" class="w-full rounded border-gray-300" required>
                    @error('phone_number')
                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm mb-1 font-medium">Opérateur <span
                            class="text-gray-400 font-normal">(optionnel)</span></label>
                    @if (!empty($providers))
                        <select name="provider" class="w-full rounded border-gray-300">
                            <option value="">— Choisir un opérateur —</option>
                            @foreach ($providers as $p)
                                <option value="{{ $p['provider'] }}" @selected(old('provider') === $p['provider'])>
                                    {{ $p['provider'] }}{{ $p['status'] !== 'OPERATIONAL' ? ' (' . $p['status'] . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" name="provider" value="{{ old('provider') }}"
                            placeholder="e.g. ORANGE_COD or AIRTEL_COD" class="w-full rounded border-gray-300">
                    @endif
                    @error('provider')
                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-4">
                    <div class="flex-1">
                        <label class="block text-sm mb-1 font-medium">Montant</label>
                        <input type="number" step="0.01" name="amount" value="{{ old('amount') }}"
                            class="w-full rounded border-gray-300" required>
                        @error('amount')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="w-28">
                        <label class="block text-sm mb-1 font-medium">Devise</label>
                        <select name="currency" class="w-full rounded border-gray-300" required>
                            <option value="CDF" @selected(old('currency', 'CDF') === 'CDF')>CDF</option>
                            <option value="USD" @selected(old('currency') === 'USD')>USD</option>
                        </select>
                        @error('currency')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm mb-1 font-medium">Description <span
                            class="text-gray-400 font-normal">(optionnel)</span></label>
                    <input type="text" name="description" value="{{ old('description') }}"
                        class="w-full rounded border-gray-300">
                    @error('description')
                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between pt-2">
                    <button type="submit" class="bg-indigo-600 text-white rounded px-5 py-2 hover:bg-indigo-700">
                        Envoyer le paiement
                    </button>
                    <a href="{{ route('admin.payouts.history') }}" class="text-sm text-gray-500 hover:underline">
                        Voir l'historique →
                    </a>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
