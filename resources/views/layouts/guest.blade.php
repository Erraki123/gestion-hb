<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('commerce.company.name') }} · Connexion</title>

    {{-- HN BLOOM Favicon --}}
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png"
          sizes="192x192"
          href="{{ asset('icons/icon-192.png') }}">

    {{-- PWA Manifest --}}
    <link rel="manifest" href="{{ asset('manifest.json') }}">

    {{-- Apple --}}
    <link rel="apple-touch-icon"
          href="{{ asset('icons/apple-touch-icon.png') }}">

    {{-- Theme --}}
    <meta name="theme-color" content="#090909">
    <meta name="application-name" content="HN BLOOM">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="HN BLOOM">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-white font-sans text-gray-800 antialiased">

    <div class="min-h-screen lg:grid lg:grid-cols-2">

        {{-- PANNEAU GAUCHE --}}
        <div class="relative hidden flex-col justify-between overflow-hidden bg-gray-950 p-12 text-white lg:flex">

            <div class="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-gold-500/10 blur-3xl"></div>
            <div class="absolute -bottom-32 -left-16 h-80 w-80 rounded-full bg-gold-500/5 blur-3xl"></div>

            <div class="relative flex items-center gap-3">
                <x-brand-mark class="h-10 w-10" />
                <span class="text-lg font-semibold">
                    {{ config('commerce.company.name') }}
                </span>
            </div>

            <div class="relative">
                <h1 class="text-4xl font-semibold leading-tight">
                    Pilotez vos ventes,<br>
                    <span class="text-gold-400">encaissez plus vite.</span>
                </h1>

                <p class="mt-4 max-w-md text-gray-400">
                    Clients, devis, factures, paiements et commissions :
                    tout votre cycle commercial au même endroit.
                </p>
            </div>

            <p class="relative text-xs text-gray-500">
                © {{ date('Y') }} {{ config('commerce.company.name') }}
            </p>
        </div>

        {{-- PANNEAU DROIT --}}
        <div class="flex items-center justify-center p-6 sm:p-12">

            <div class="w-full max-w-sm">

                {{-- LOGO MOBILE --}}
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <x-brand-mark class="h-9 w-9" />
                    <span class="font-semibold text-gray-900">
                        {{ config('commerce.company.name') }}
                    </span>
                </div>

                {{-- CONTENU LOGIN --}}
                {{ $slot }}

                {{-- SEPARATEUR --}}
                <div class="my-6 flex items-center gap-3">
                    <div class="h-px flex-1 bg-gray-200"></div>
                    <span class="text-xs font-medium uppercase tracking-wider text-gray-400">
                        Application
                    </span>
                    <div class="h-px flex-1 bg-gray-200"></div>
                </div>

                {{-- BOUTON INSTALLATION PWA --}}
                <button
                    type="button"
                    id="install-app-button"
                    hidden
                    class="group flex w-full items-center justify-center gap-3 rounded-xl border border-gray-900 bg-gray-950 px-5 py-3.5 text-sm font-semibold text-white shadow-lg shadow-gray-900/10 transition duration-200 hover:border-gold-500 hover:bg-black focus:outline-none focus:ring-2 focus:ring-gold-500 focus:ring-offset-2">

                    <svg class="h-5 w-5 text-gold-400 transition group-hover:scale-110"
                         viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="1.8"
                         stroke-linecap="round"
                         stroke-linejoin="round"
                         aria-hidden="true">
                        <rect x="4" y="3" width="16" height="18" rx="2"></rect>
                        <path d="M12 7v8"></path>
                        <path d="m9 12 3 3 3-3"></path>
                    </svg>

                    <span id="install-app-label">
                        Installer HN BLOOM
                    </span>

                    <svg class="h-4 w-4 text-gray-400 transition group-hover:translate-x-1 group-hover:text-gold-400"
                         viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="2"
                         stroke-linecap="round"
                         stroke-linejoin="round"
                         aria-hidden="true">
                        <path d="M5 12h14"></path>
                        <path d="m12 5 7 7-7 7"></path>
                    </svg>
                </button>

                {{-- INSTRUCTIONS IPHONE --}}
                <div id="ios-install-help"
                     hidden
                     class="mt-3 rounded-xl border border-gold-200 bg-amber-50 p-4 text-sm text-gray-700">

                    <p class="font-semibold text-gray-900">
                        Installer sur iPhone / iPad
                    </p>

                    <ol class="mt-2 list-decimal space-y-2 pl-5">
                        <li>
                            Appuyez sur le bouton
                            <strong>Partager</strong>
                            <span aria-hidden="true">⎋</span>
                            dans Safari.
                        </li>
                        <li>
                            Choisissez
                            <strong>Sur l’écran d’accueil</strong>.
                        </li>
                        <li>
                            Appuyez sur <strong>Ajouter</strong>.
                        </li>
                    </ol>
                </div>

                <p id="install-app-status"
                   class="mt-3 text-center text-xs text-gray-400"
                   role="status"
                   aria-live="polite"></p>

            </div>
        </div>
    </div>

    {{-- SERVICE WORKER + INSTALLATION PWA --}}
    <script>
        // Enregistrement du Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register(
                    '{{ asset("service-worker.js") }}'
                ).catch(error => {
                    console.error('HN BLOOM Service Worker:', error);
                });
            });
        }

        // Gestion du bouton d'installation
        (() => {
            const installButton = document.getElementById('install-app-button');
            const installLabel = document.getElementById('install-app-label');
            const iosHelp = document.getElementById('ios-install-help');
            const status = document.getElementById('install-app-status');

            if (!installButton) return;

            let deferredPrompt = null;

            const isIOS = /iphone|ipad|ipod/i.test(navigator.userAgent);

            const isStandalone =
                window.matchMedia('(display-mode: standalone)').matches ||
                window.navigator.standalone === true;

            // Déjà installé : ne pas afficher le bouton
            if (isStandalone) {
                installButton.hidden = true;
                return;
            }

            // iPhone / iPad : afficher les instructions Safari
            if (isIOS) {
                installButton.hidden = false;
                installLabel.textContent = 'Installer sur mon iPhone';
            }

            // Le navigateur indique que l'application est installable
            window.addEventListener('beforeinstallprompt', (event) => {
                event.preventDefault();

                deferredPrompt = event;
                installButton.hidden = false;

                installLabel.textContent = 'Installer HN BLOOM';
            });

            // Clic sur le bouton
            installButton.addEventListener('click', async () => {

                // Instructions iOS
                if (isIOS) {
                    iosHelp.hidden = !iosHelp.hidden;
                    return;
                }

                // Installation Chrome / Edge / Android
                if (deferredPrompt) {
                    deferredPrompt.prompt();

                    const choice = await deferredPrompt.userChoice;

                    if (choice.outcome === 'accepted') {
                        status.textContent = 'Installation lancée.';
                        installButton.hidden = true;
                    } else {
                        status.textContent = 'Installation annulée.';
                    }

                    deferredPrompt = null;
                    return;
                }

                status.textContent =
                    'L’installation n’est pas disponible dans ce navigateur pour le moment.';
            });

            // Application installée
            window.addEventListener('appinstalled', () => {
                installButton.hidden = true;
                iosHelp.hidden = true;
                status.textContent = 'HN BLOOM est installé.';
            });

        })();
    </script>

</body>
</html>