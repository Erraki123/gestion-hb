<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2>Fournisseurs</h2>
                <p class="mt-1 text-sm text-gray-500">Liste des fournisseurs</p>
            </div>
            <a href="{{ route('suppliers.create') }}" class="px-3 py-2 rounded bg-gold-500 text-gray-900">Nouveau fournisseur</a>
        </div>
    </x-slot>

    <div class="py-8 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="panel overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left">
                        <tr>
                            <th class="p-4">Nom</th>
                            <th class="p-4">Société</th>
                            <th class="p-4">Téléphone</th>
                            <th class="p-4">Email</th>
                            <th class="p-4"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($suppliers as $supplier)
                        <tr>
                            <td class="p-4"><a href="{{ route('suppliers.show', $supplier) }}" class="text-indigo-600">{{ $supplier->name }}</a></td>
                            <td class="p-4">{{ $supplier->company ?: '—' }}</td>
                            <td class="p-4">{{ $supplier->phone ?: '—' }}</td>
                            <td class="p-4">{{ $supplier->email ?: '—' }}</td>
                            <td class="p-4 text-right">
                                <a href="{{ route('suppliers.edit', $supplier) }}" class="text-sm text-gold-600">Modifier</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
