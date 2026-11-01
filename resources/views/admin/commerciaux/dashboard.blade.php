<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Tableau de bord Admin</h2>
    </x-slot>
    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white p-6 rounded shadow">Bienvenue, {{ auth()->user()->name }}.</div>
    </div>
</x-app-layout>
