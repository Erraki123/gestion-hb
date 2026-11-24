
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('commerce.company.name') }} · Gestion commerciale</title>

    {{-- HN BLOOM Favicon --}}
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png"
          sizes="192x192"
          href="{{ asset('icons/icon-192.png') }}">

    {{-- PWA Manifest --}}
    <link rel="manifest" href="{{ asset('manifest.json') }}">

    {{-- Apple Touch Icon --}}
    <link rel="apple-touch-icon"
          href="{{ asset('icons/apple-touch-icon.png') }}">

    {{-- Application Theme --}}
    <meta name="theme-color" content="#090909">
    <meta name="application-name" content="HN BLOOM">

    {{-- iOS PWA --}}
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="HN BLOOM">

    @vite(['resources/css/app.css', 'resources/css/hnb-finish.css', 'resources/js/app.js'])
    <!-- HNB_UI_SPRINT2 -->
    <link rel="stylesheet" href="{{ asset('css/hnb-premium-v2.css') }}">
    <!-- HNB_UI_SPRINT3_CSS -->
    <link rel="stylesheet" href="{{ asset('css/hnb-ui-sprint3.css') }}">
    <!-- HNB LOCAL UNIFIED NEW PAGES V1 -->
    <link rel="stylesheet" href="{{ asset('css/hnb-unified-pages-v1.css') }}">
</head>

<body class="hnb-ui bg-white text-gray-900 antialiased font-sans"
      x-data="{ sidebar: false, search: false }"
      @keydown.ctrl.k.window.prevent="search = true"
      @keydown.meta.k.window.prevent="search = true"
      @keydown.escape.window="sidebar = false">

    <div class="min-h-screen">

        {{-- Fond sombre : clic = fermer le menu --}}
        <div x-show="sidebar" x-cloak
             x-transition.opacity.duration.200ms
             @click="sidebar = false"
             class="fixed inset-0 z-30 bg-black/50"></div>

        {{-- Menu latéral --}}
        <aside id="hnb-main-sidebar" :inert="!sidebar" :class="sidebar ? 'translate-x-0 shadow-2xl' : '-translate-x-full'"
               :aria-hidden="(!sidebar).toString()"
               class="fixed inset-y-0 left-0 z-40 flex w-64 shrink-0 -translate-x-full flex-col bg-gray-950 transition-transform duration-200">

            @include('layouts.navigation')
        </aside>

        <div class="flex min-h-screen min-w-0 flex-col">

            {{-- Barre supérieure --}}
            <div class="hnb-topbar sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-gray-200 bg-white/90 px-4 backdrop-blur sm:px-6 lg:px-8">

                {{-- Menu Burger --}}
                <button type="button"
                        @click="sidebar = !sidebar"
                        :aria-expanded="sidebar.toString()"
                        aria-controls="hnb-main-sidebar"
                        class="rounded-lg border border-gray-200 p-2 text-gray-900 transition hover:border-gold-500 hover:bg-gold-50"
                        aria-label="Menu">

                    <x-icon name="menu" />
                </button>

                {{-- Recherche --}}
                <button type="button"
                        @click="search = true"
                        class="flex min-w-0 flex-1 max-w-xl items-center gap-2 rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-500 transition hover:border-gold-500 sm:px-4"
                        aria-label="Recherche globale">

                    <x-icon name="search" class="h-4 w-4 shrink-0" />

                    <span class="hidden min-w-0 flex-1 truncate text-left sm:block">
                        Rechercher un client, une facture, un produit…
                    </span>

                    <kbd class="hidden rounded border border-gray-300 bg-white px-1.5 text-[11px] text-gray-500 sm:inline">
                        Ctrl K
                    </kbd>
                </button>

                <div class="ml-auto flex items-center gap-2">

                    {{-- Alarme chèques --}}
                    <x-cheque-bell />

                    {{-- Compte utilisateur --}}
                    <x-dropdown align="right" width="48">

                        <x-slot name="trigger">
                            <button class="flex items-center gap-3 rounded-xl px-2 py-1 hover:bg-gold-50">

                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-gold-300 to-gold-600 text-sm font-semibold text-black">
                                    {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 2)) }}
                                </span>

                                <span class="hidden text-left leading-tight sm:block">
                                    <span class="block text-sm font-semibold text-gray-900">
                                        {{ auth()->user()->name }}
                                    </span>

                                    <span class="block text-[11px] text-gray-500">
                                        {{ auth()->user()->isAdmin() ? 'Administrateur' : 'Commercial' }}
                                    </span>
                                </span>

                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')">
                                Mon profil
                            </x-dropdown-link>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                    Déconnexion
                                </x-dropdown-link>
                            </form>
                        </x-slot>

                    </x-dropdown>
                </div>
            </div>

            {{-- En-tête des pages --}}
            @isset($header)
                <div class="page-head border-b {{ $attributes->has('dark-header') ? '!border-gray-800 !bg-gray-950 !bg-none [&_h2]:!text-white [&_p]:!text-gray-400' : 'border-gray-200 bg-white' }}">

                    <div class="px-4 py-6 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>

                </div>
            @endisset

            {{-- Contenu principal --}}
            <main class="flex-1 bg-white">
                {{ $slot }}
            </main>

            {{-- Pied de page --}}
            <footer class="px-4 py-4 text-center text-xs text-gray-400 sm:px-6 lg:px-8">
                © {{ date('Y') }} {{ config('commerce.company.name') }}
            </footer>

        </div>
    </div>

    {{-- Fenêtres et recherche --}}
    @include('layouts._confirm')
    @include('layouts._search')
    @include('layouts._ai_floating')

    {{-- HN BLOOM PWA Service Worker Registration --}}
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register(
                    '{{ asset("service-worker.js") }}'
                )
                .then((registration) => {
                    console.log(
                        'HN BLOOM Service Worker registered:',
                        registration.scope
                    );
                })
                .catch((error) => {
                    console.error(
                        'HN BLOOM Service Worker error:',
                        error
                    );
                });
            });
        }
    </script>

</body>
</html>
