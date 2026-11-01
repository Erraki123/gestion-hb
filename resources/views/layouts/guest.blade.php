<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('commerce.company.name') }} · Connexion</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white font-sans text-gray-800 antialiased">
    <div class="min-h-screen lg:grid lg:grid-cols-2">

        <div class="relative hidden flex-col justify-between overflow-hidden bg-gray-950 p-12 text-white lg:flex">
            <div class="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-gold-500/10 blur-3xl"></div>
            <div class="absolute -bottom-32 -left-16 h-80 w-80 rounded-full bg-gold-500/5 blur-3xl"></div>

            <div class="relative flex items-center gap-3">
                <x-brand-mark class="h-10 w-10" />
                <span class="text-lg font-semibold">{{ config('commerce.company.name') }}</span>
            </div>

            <div class="relative">
                <h1 class="text-4xl font-semibold leading-tight">
                    Pilotez vos ventes,<br><span class="text-gold-400">encaissez plus vite.</span>
                </h1>
                <p class="mt-4 max-w-md text-gray-400">
                    Clients, devis, factures, paiements et commissions : tout votre cycle commercial au même endroit.
                </p>
            </div>

            <p class="relative text-xs text-gray-500">© {{ date('Y') }} {{ config('commerce.company.name') }}</p>
        </div>

        <div class="flex items-center justify-center p-6 sm:p-12">
            <div class="w-full max-w-sm">
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <x-brand-mark class="h-9 w-9" />
                    <span class="font-semibold text-gray-900">{{ config('commerce.company.name') }}</span>
                </div>

                {{ $slot }}
            </div>
        </div>
    </div>
</body>
</html>