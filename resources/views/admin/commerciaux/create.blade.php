<x-app-layout>
    <x-slot name="header"><h2>Nouveau commercial</h2></x-slot>
    <div class="py-8 max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('admin.commerciaux.store') }}" class="panel p-6 sm:p-8">
            @include('admin.commerciaux._form')
        </form>
    </div>
</x-app-layout>
