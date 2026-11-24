<x-app-layout dark-header>
    <x-slot name="header">
        <div class="relative flex items-center justify-center text-center">
            <div>
                <h2 class="justify-center !text-lg !leading-tight">Nouveau client</h2>
                <p class="text-xs text-gray-500">Les champs marqués * sont obligatoires.</p>
            </div>
            <a href="{{ route('clients.index') }}"
               class="absolute right-0 top-1/2 -translate-y-1/2 inline-flex items-center gap-1 rounded-lg border border-gold-300 bg-gold-100 px-3 py-1.5 text-xs font-semibold text-gray-900 shadow-sm transition hover:bg-gold-200">
                ← Retour à la liste
            </a>
        </div>
    </x-slot>

    {{-- PC (lg+) : header réduit + formulaire compact, le bouton Enregistrer reste visible --}}
    <style>
        @media (min-width: 1024px) {
            /* Header plus petit : on réduit le padding vertical du conteneur d'en-tête */
            header, header > div { padding-top: .5rem !important; padding-bottom: .5rem !important; }

            .create-fit form { display: flex; flex-direction: column; }

            .create-fit form > .grid { gap: 1rem; align-items: start; }
            .create-fit form > .grid > .panel { padding: .75rem 1rem; }
            .create-fit .panel-title { margin-bottom: .4rem; font-size: .95rem; }

            /* Panneau gauche : 2 colonnes, sans grands espaces */
            .create-fit form > .grid > .panel:first-child .space-y-4 {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: .6rem .9rem;
            }
            .create-fit form > .grid > .panel:first-child .space-y-4 > * { margin-top: 0 !important; }
            .create-fit form > .grid > .panel:first-child .space-y-4 > div:first-child { grid-column: span 2; }

            /* Champs compacts */
            .create-fit label { font-size: .78rem; }
            .create-fit input[type=text], .create-fit input[type=email], .create-fit select {
                padding-top: .3rem; padding-bottom: .3rem; font-size: .85rem;
            }
            .create-fit .mt-1 { margin-top: .15rem; }
            .create-fit textarea { height: 48px; min-height: 0; }

            /* Photos compactes */
            .create-fit .mt-6.border-t { margin-top: .5rem; padding-top: .5rem; }
            .create-fit .mt-6.border-t .panel-title { margin-bottom: .3rem; }
            .create-fit [id^="placeholder-"], .create-fit [id^="preview-"] { height: 2.4rem; margin-bottom: .2rem; }
            .create-fit .border-dashed { padding: .35rem; }
            .create-fit input[type=file] { margin-top: .2rem; font-size: .7rem; }
            .create-fit .mt-2.text-xs { margin-top: .25rem; }

            /* Boutons */
            .create-fit form > div:last-child { margin-top: .5rem; }
        }
    </style>

    <div class="create-fit py-2 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('clients.store') }}" enctype="multipart/form-data">
            @include('clients._form')
        </form>
    </div>
</x-app-layout>