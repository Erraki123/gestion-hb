<x-app-layout>
    <x-slot name="header">
        <h2>Nouveau devis</h2>
        <p class="mt-1 text-sm text-gray-500">Choisissez un client puis ajoutez les produits</p>
    </x-slot>
    <div class="py-8 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('quotes.store') }}" class="panel p-6 sm:p-8">
            @include('quotes._form')
        </form>
    </div>
</x-app-layout>