<x-app-layout>
    <x-slot name="header">
        <h2>Modifier {{ $quote->number }}</h2>
        <p class="mt-1 text-sm text-gray-500">Devis en brouillon</p>
    </x-slot>
    <div class="py-8 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('quotes.update', $quote) }}" class="panel p-6 sm:p-8">
            @method('PUT')
            @include('quotes._form')
        </form>
    </div>
</x-app-layout>
