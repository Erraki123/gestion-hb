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
<body class="bg-[#0B0B0A] text-[#F4F2EC] antialiased font-sans"
      x-data="{ sidebar: false, search: false }"
      @keydown.ctrl.k.window.prevent="search = true"
      @keydown.meta.k.window.prevent="search = true">

    <div class="min-h-screen lg:flex">

        <div x-show="sidebar" x-cloak @click="sidebar = false" class="fixed inset-0 z-30 bg-black/70 lg:hidden"></div>

        {{-- Menu latéral --}}
        <aside :class="sidebar ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
               class="fixed inset-y-0 left-0 z-40 flex w-64 shrink-0 flex-col border-r border-white/5 bg-[#0D0D0C] transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen">
            @include('layouts.navigation')
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">

            {{-- Barre du haut --}}
            <div class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-white/5 bg-[#12110F]/90 px-4 backdrop-blur sm:px-6 lg:px-8">
                <button type="button" @click="sidebar = true" class="rounded-md p-2 text-gray-300 hover:bg-white/5 lg:hidden" aria-label="Menu">
                    <x-icon name="menu" />
                </button>

                <button type="button" @click="search = true"
                        class="flex w-full max-w-xl items-center gap-2 rounded-xl border border-[#2A2822] bg-[#1A1915] px-4 py-2.5 text-sm text-gray-400 transition hover:border-gold-500/50">
                    <x-icon name="search" class="h-4 w-4" />
                    <span class="flex-1 text-left">Rechercher un client, une facture, un produit…</span>
                    <kbd class="hidden rounded border border-[#3A372F] bg-[#171613] px-1.5 text-[11px] text-gray-400 sm:inline">Ctrl K</kbd>
                </button>

                <div class="ml-auto">
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="flex items-center gap-3 rounded-xl px-2 py-1 hover:bg-white/5">
                                <span class="flex h-10 w-10 items-center justify-center rounded-full border border-gold-500/60 bg-[#1A1915] text-sm font-semibold text-gold-400">
                                    {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 2)) }}
                                </span>
                                <span class="hidden text-left leading-tight sm:block">
                                    <span class="block text-sm font-semibold text-[#F4F2EC]">{{ auth()->user()->name }}</span>
                                    <span class="block text-[11px] text-gray-400">
                                        {{ auth()->user()->isAdmin() ? 'Administrateur' : 'Commercial' }}
                                    </span>
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

            {{-- Titre de la page --}}
            @isset($header)
                <div class="page-head border-b border-white/5 bg-gradient-to-b from-[#171613] to-transparent">
                    <div class="px-4 py-6 sm:px-6 lg:px-8">{{ $header }}</div>
                </div>
            @endisset

            <main class="flex-1 bg-[#0B0B0A]">{{ $slot }}</main>

            <footer class="px-4 py-4 text-center text-xs text-gray-400 sm:px-6 lg:px-8">
                © {{ date('Y') }} {{ config('commerce.company.name') }}
            </footer>
        </div>
    </div>

    @include('layouts._confirm')
    @include('layouts._search')
</body>
</html>
