<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('commerce.company.name') }} · Gestion commerciale</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800 antialiased font-sans"
      x-data="{ sidebar: false, search: false }"
      @keydown.ctrl.k.window.prevent="search = true"
      @keydown.meta.k.window.prevent="search = true">

    <div class="min-h-screen lg:flex">

        <div x-show="sidebar" x-cloak @click="sidebar = false" class="fixed inset-0 z-30 bg-black/50 lg:hidden"></div>

        <aside :class="sidebar ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
               class="fixed inset-y-0 left-0 z-40 flex w-64 shrink-0 flex-col bg-gray-950 transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen">
            @include('layouts.navigation')
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">

            <div class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-gray-200 bg-white/90 px-4 backdrop-blur sm:px-6 lg:px-8">
                <button type="button" @click="sidebar = true" class="rounded-md p-2 text-gray-600 hover:bg-gray-100 lg:hidden" aria-label="Menu">
                    <x-icon name="menu" />
                </button>

                <button type="button" @click="search = true"
                        class="flex w-full max-w-xl items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-500 transition hover:border-gold-300 hover:bg-white">
                    <x-icon name="search" class="h-4 w-4" />
                    <span class="flex-1 text-left">Rechercher un client, une facture, un produit…</span>
                    <kbd class="hidden rounded border border-gray-300 bg-white px-1.5 text-[11px] text-gray-500 sm:inline">Ctrl K</kbd>
                </button>

                <div class="ml-auto">
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="flex items-center gap-3 rounded-xl px-2 py-1 hover:bg-gray-100">
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-gold-500 to-orange-500 text-sm font-semibold text-white">
                                    {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 2)) }}
                                </span>
                                <span class="hidden text-left leading-tight sm:block">
                                    <span class="block text-sm font-semibold text-gray-900">{{ auth()->user()->name }}</span>
                                    <span class="block text-[11px] text-gray-500">{{ auth()->user()->isAdmin() ? 'Administrateur' : 'Commercial' }}</span>
                                </span>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')">Mon profil</x-dropdown-link>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                    Déconnexion
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>
            </div>

            @isset($header)
                <div class="page-head border-b border-gray-200 bg-white">
                    <div class="px-4 py-6 sm:px-6 lg:px-8">{{ $header }}</div>
                </div>
            @endisset

            <main class="flex-1">{{ $slot }}</main>

            <footer class="px-4 py-4 text-center text-xs text-gray-400 sm:px-6 lg:px-8">
                © {{ date('Y') }} {{ config('commerce.company.name') }}
            </footer>
        </div>
    </div>

    @include('layouts._confirm')
    @include('layouts._search')
</body>
</html>