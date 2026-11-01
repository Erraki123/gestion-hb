@props(['code', 'title', 'message'])
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} · {{ $title }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-gray-950 p-6 font-sans antialiased">
    <div class="max-w-md text-center">
        <div class="text-8xl font-semibold text-gold-400">{{ $code }}</div>
        <h1 class="mt-4 text-2xl font-semibold text-white">{{ $title }}</h1>
        <p class="mt-2 text-gray-400">{{ $message }}</p>
        <div class="mt-8 flex justify-center gap-3">
            <a href="javascript:history.back()" class="rounded-md border border-gray-700 px-4 py-2 text-sm text-gray-300 hover:bg-white/5">Retour</a>
            <a href="{{ url('/dashboard') }}" class="rounded-md bg-gold-500 px-4 py-2 text-sm font-semibold text-gray-950 hover:bg-gold-400">Tableau de bord</a>
        </div>
    </div>
</body>
</html>