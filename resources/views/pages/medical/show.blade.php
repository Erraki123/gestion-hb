@php
    $crumbs = [
        ['Domotique', route('domotique')],
        ['Smart Hospital', route('domotique.show', 'smart-hospital')],
        [$solution['title'], route('medical.show', $slug)],
    ];
@endphp

<x-hb-layout
    :title="$solution['title'].' — SMART G4 CONTROL | HN BLOOM'"
    :description="$solution['description']"
    :breadcrumbs="$crumbs">

    @if (in_array($slug, ['ncs', 'health-spa']))
        {{-- NCS / Health SPA : uniquement la démo panorama, plein écran (comme Smart Hotel) --}}
        <div class="hb-smart-page" style="position:relative;isolation:isolate;clip-path:inset(0);background:#090908;color:#f6f1e7">
            @include('pages.medical.'.$slug)
        </div>
    @else
        <link rel="stylesheet" href="{{ asset('css/hnb-medical.css') }}">

        <section class="hnb-medical">

            <a class="hm-back"
               href="{{ route('domotique.show', 'smart-hospital') }}">
                &#8592; Retour à Smart Hospital
            </a>

            <header class="hm-heading">

                <span class="hm-eyebrow">
                    SMART G4 CONTROL · {{ $solution['category'] }}
                </span>

                <h1>{{ $solution['title'] }}</h1>

                <p>{{ $solution['description'] }}</p>

            </header>

            @include('pages.medical.'.$slug)

            <nav class="hm-navigation" aria-label="Solutions médicales">

                @foreach($medical as $key => $item)

                    <a href="{{ route('medical.show', $key) }}"
                       @if($key === $slug)
                           class="is-active" aria-current="page"
                       @endif>
                        {{ $item['short'] }}
                    </a>

                @endforeach

            </nav>

            <a class="hm-contact"
               href="{{ route('contact', ['sujet' => 'quote']) }}">
                Demander une étude personnalisée &#8599;
            </a>

        </section>
    @endif

</x-hb-layout>
