<x-app-layout dark-header>
    <style>header, header > div { padding-top:.5rem !important; padding-bottom:.5rem !important; }</style>

    <x-slot name="header">
        <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-2">
            <div class="flex justify-start">
                <a href="{{ route('products.index') }}" class="inline-flex items-center gap-1 rounded-lg border border-gold-300 bg-gold-100 px-3 py-1.5 text-xs font-semibold text-gray-900 shadow-sm transition hover:bg-gold-200">← Retour</a>
            </div>
            <div class="text-center">
                <h2 class="justify-center !text-lg !leading-tight">Catégories</h2>
                <p class="text-xs text-gray-500">{{ $categories->count() }} catégorie(s)</p>
            </div>
            <span></span>
        </div>
    </x-slot>

    <div class="py-5 max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-3">
        @if (session('success'))
            <div class="rounded-xl border border-green-200 bg-green-100 p-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-xl border border-red-200 bg-red-100 p-3 text-sm text-red-800">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-100 p-3 text-sm text-red-800">
                @foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach
            </div>
        @endif

        {{-- Ajout rapide : tapez le nom puis Entrée --}}
        <form method="POST" action="{{ route('categories.store') }}" class="panel flex gap-2 p-2.5">
            @csrf
            <input type="text" name="name" value="{{ old('name') }}" placeholder="Nouvelle catégorie…" class="flex-1 !py-1.5 text-sm" required autofocus>
            <button class="rounded-lg bg-gold-500 px-5 py-1.5 text-sm font-semibold text-gray-950 hover:bg-gold-400">Ajouter</button>
        </form>

        <div class="panel divide-y divide-white/5">
            @forelse ($categories as $c)
                <div class="flex items-center justify-between gap-3 px-3 py-2">
                    <div class="flex min-w-0 items-center gap-2">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-gold-500/30 bg-gold-500/10 text-gold-400"><x-icon name="package" class="h-4 w-4" /></span>
                        <div class="min-w-0">
                            <div class="truncate text-sm font-medium text-gray-900">{{ $c->name }}</div>
                            <div class="text-[11px] text-gray-500">{{ $c->products_count }} produit(s)</div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('categories.destroy', $c) }}"
                          onsubmit="return confirm('Supprimer « {{ addslashes($c->name) }} » ?')">
                        @csrf @method('DELETE')
                        <button class="act act-danger">Supprimer</button>
                    </form>
                </div>
            @empty
                <p class="p-8 text-center text-sm text-gray-500">Aucune catégorie. Ajoutez la première ci-dessus.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
