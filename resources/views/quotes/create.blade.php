<x-app-layout dark-header>
    <x-slot name="header">
        <div class="relative flex items-center justify-center text-center">
            <div>
                <h2 class="justify-center !text-lg !leading-tight">Nouveau devis</h2>
                <p class="text-xs text-gray-500">Choisissez un client, puis ajoutez les produits</p>
            </div>
            <a href="{{ route('quotes.index') }}"
               class="absolute right-0 top-1/2 -translate-y-1/2 inline-flex items-center gap-1 rounded-lg border border-gold-300 bg-gold-100 px-3 py-1.5 text-xs font-semibold text-gray-900 shadow-sm transition hover:bg-gold-200">
                ← Retour à la liste
            </a>
        </div>
    </x-slot>

    {{-- Affichage compact : header réduit + formulaire serré, tout visible sur PC --}}
    <style>
        header, header > div { padding-top: .5rem !important; padding-bottom: .5rem !important; }

        @media (min-width: 1024px) {
            .quote-fit { padding-top: .5rem; padding-bottom: .5rem; }

            /* Panneaux : moins de padding, moins d'espace entre eux */
            .quote-fit .panel { padding: .75rem 1rem !important; }
            .quote-fit .panel-title { margin-bottom: .4rem; font-size: .95rem; }
            .quote-fit .grid { gap: .75rem !important; }
            .quote-fit .space-y-4 > :not([hidden]) ~ :not([hidden]),
            .quote-fit .space-y-5 > :not([hidden]) ~ :not([hidden]),
            .quote-fit .space-y-6 > :not([hidden]) ~ :not([hidden]) { margin-top: .5rem !important; }

            /* Champs compacts */
            .quote-fit label { font-size: .75rem; margin-bottom: 0; }
            .quote-fit input:not([type=checkbox]):not([type=radio]),
            .quote-fit select {
                padding-top: .3rem !important; padding-bottom: .3rem !important; font-size: .82rem;
            }
            .quote-fit .mt-1 { margin-top: .15rem !important; }
            .quote-fit textarea { height: 54px !important; min-height: 0 !important; font-size: .82rem; padding-top: .35rem; padding-bottom: .35rem; }

            /* Bloc produit */
            .quote-fit .panel .rounded-xl { padding: .6rem !important; }

            /* Récapitulatif compact */
            .quote-fit .text-lg { font-size: 1rem; }

            /* Boutons du bas */
            .quote-fit form > div:last-child { margin-top: .5rem !important; }
        }
    </style>

    <div class="quote-fit py-3 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('quotes.store') }}">
            @include('quotes._form')
        </form>
    </div>
</x-app-layout>