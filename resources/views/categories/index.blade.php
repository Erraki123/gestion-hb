<x-app-layout>
    <x-slot name="header">
        <h2>Catégories</h2>
        <p class="mt-1 text-sm text-gray-500">Organisez vos produits</p>
    </x-slot>
    <div class="py-8 max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
        @if (session('success'))
            <div class="rounded-xl border border-green-200 bg-green-100 p-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('categories.store') }}" class="panel flex gap-3 p-3">
            @csrf
            <input type="text" name="name" placeholder="Nouvelle catégorie" class="flex-1" required>
            <x-primary-button>Ajouter</x-primary-button>
        </form>
        <x-input-error :messages="$errors->get('name')" />

        <div class="panel divide-y divide-white/5">
            @forelse ($categories as $c)
                <div class="flex items-center justify-between gap-3 p-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl border border-gold-500/30 bg-gold-500/10 text-gold-400"><x-icon name="package" class="h-4 w-4" /></span>
                        <div>
                            <div class="font-medium text-gray-900">{{ $c->name }}</div>
                            <div class="text-xs text-gray-500">{{ $c->products_count }} produit(s)</div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('categories.destroy', $c) }}" onsubmit="return confirm('Supprimer ?')">
                        @csrf @method('DELETE')
                        <button class="act act-danger">Supprimer</button>
                    </form>
                </div>
            @empty
                <p class="p-10 text-center text-sm text-gray-500">Aucune catégorie.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
