<x-app-layout>
    <x-slot name="header">
        <h2>Nouveau client</h2>
        <p class="mt-1 text-sm text-gray-500">Renseignez les informations du client</p>
    </x-slot>
    <div class="py-8 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('clients.store') }}" enctype="multipart/form-data" class="panel p-6 sm:p-8">
            @include('clients._form')
        </form>
    </div>
</x-app-layout>
