<x-app-layout dark-header>
    <style>header, header > div { padding-top:.5rem !important; padding-bottom:.5rem !important; }</style>
    <x-slot name="header">
        <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-2">
            <a href="{{ route('products.index') }}" class="inline-flex items-center gap-1 rounded-lg border border-gold-300 bg-gold-100 px-3 py-1.5 text-xs font-semibold text-gray-900 shadow-sm transition hover:bg-gold-200">← Retour</a>
            <div class="text-center">
                <h2 class="justify-center !text-lg !leading-tight">Nouveau produit</h2>
                <p class="text-xs text-gray-500">Remplissez puis enregistrez</p>
            </div>
            <span></span>
        </div>
    </x-slot>

    <div class="py-5 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" class="panel p-4 sm:p-6">
            @include('products._form')
        </form>
    </div>
</x-app-layout>
