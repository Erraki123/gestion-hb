<x-app-layout>
    <x-slot name="header">
        <h2>Modifier le client</h2>
        <p class="mt-1 text-sm text-gray-500">{{ $client->name }}</p>
    </x-slot>
    <div class="py-8 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('clients.update', $client) }}" class="panel p-6 sm:p-8">
            @method('PUT')
            @include('clients._form')
        </form>
    </div>
</x-app-layout>
