<x-app-layout dark-header>
    <x-slot name="header">
        <div class="relative flex items-center justify-center text-center">
            <div>
                <h2 class="justify-center !text-lg !leading-tight">Modifier {{ $quote->number }}</h2>
                <p class="text-xs text-gray-500">Devis en brouillon</p>
            </div>
            <a href="{{ route('quotes.show', $quote) }}"
               class="absolute right-0 top-1/2 -translate-y-1/2 inline-flex items-center gap-1 rounded-lg border border-gold-300 bg-gold-100 px-3 py-1.5 text-xs font-semibold text-gray-900 shadow-sm transition hover:bg-gold-200">
                ← Retour au devis
            </a>
        </div>
    </x-slot>

    <style>
        header, header > div { padding-top: .5rem !important; padding-bottom: .5rem !important; }
    </style>

    <div class="py-3 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('quotes.update', $quote) }}">
            @method('PUT')
            @include('quotes._form')
        </form>
    </div>
</x-app-layout>