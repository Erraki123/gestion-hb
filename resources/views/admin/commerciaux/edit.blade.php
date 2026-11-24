<x-app-layout dark-header>
    <x-slot name="header">
        <div class="relative flex items-center justify-center text-center">
            <a href="{{ route('admin.commerciaux.index') }}"
               class="absolute left-0 top-1/2 -translate-y-1/2 inline-flex items-center gap-1 rounded-lg border border-gold-300 bg-gold-100 px-3 py-1.5 text-xs font-semibold text-gray-900 shadow-sm transition hover:bg-gold-200">
                ← Retour
            </a>
            <div>
                <h2 class="justify-center !text-lg !leading-tight">{{ $commercial->name }}</h2>
                <p class="text-xs text-gray-500">Compte et commission</p>
            </div>
        </div>
    </x-slot>

    <style>
        header, header > div { padding-top: .5rem !important; padding-bottom: .5rem !important; }
    </style>

    <div class="py-5 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('admin.commerciaux.update', $commercial) }}">
            @method('PUT')
            @include('admin.commerciaux._form')
        </form>
    </div>
</x-app-layout>
