<x-app-layout dark-header>
    <x-slot name="header">
        <div class="relative flex items-center justify-center text-center">
            <div>
                <h2 class="justify-center !text-lg !leading-tight">Modifier le client</h2>
                <p class="text-xs text-gray-500">{{ $client->name }} · Les champs marqués * sont obligatoires.</p>
            </div>
            <a href="{{ route('clients.show', $client) }}"
               class="absolute right-0 top-1/2 -translate-y-1/2 inline-flex items-center gap-1 rounded-lg border border-gold-300 bg-gold-100 px-3 py-1.5 text-xs font-semibold text-gray-900 shadow-sm transition hover:bg-gold-200">
                ← Retour à la fiche
            </a>
        </div>
    </x-slot>

    {{-- Même présentation compacte que la page « Nouveau client » --}}
    <style>
        @media (min-width: 1024px) {
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

            /* Bloc fichiers compact */
            .create-fit .mt-6.border-t { margin-top: .5rem; padding-top: .5rem; }
            .create-fit .mt-6.border-t .panel-title { margin-bottom: .3rem; }
            .create-fit input[type=file] { margin-top: .2rem; font-size: .7rem; }

            /* Boutons */
            .create-fit form > div:last-child { margin-top: .5rem; }
        }
    </style>

    <div class="create-fit py-2 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if ($errors->any())
            <div class="mb-3 rounded-xl border border-red-200 bg-red-100 p-3 text-sm text-red-800">
                @foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('clients.update', $client) }}" enctype="multipart/form-data">
            @method('PUT')
            @include('clients._form')
        </form>
    </div>
</x-app-layout>
