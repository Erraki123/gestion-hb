<x-app-layout>
    <x-slot name="header">
        <h2>Système</h2>
        <p class="mt-1 text-sm text-gray-500">Sécurité et sauvegardes</p>
    </x-slot>

    @php
        $bad  = collect($checks)->where('ok', false);
        $crit = $bad->where('level', 'danger')->count();
    @endphp

    <div class="py-8 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
        @if (session('success'))
            <div class="rounded-xl border border-green-200 bg-green-100 p-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-100 p-3 text-sm text-red-800">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <div class="rounded-2xl border p-4 text-sm {{ $crit ? 'border-red-200 bg-red-50 text-red-800' : ($bad->isNotEmpty() ? 'border-yellow-200 bg-yellow-50 text-yellow-800' : 'border-green-200 bg-green-50 text-green-800') }}">
            @if ($crit) <strong>{{ $crit }} point(s) critique(s)</strong> à corriger.
            @elseif ($bad->isNotEmpty()) <strong>{{ $bad->count() }} avertissement(s)</strong> (normal en développement local).
            @else <strong>Tout est en ordre.</strong> @endif
        </div>

        <div class="panel overflow-x-auto">
            <table class="min-w-full text-sm">
                <tbody>
                @foreach ($checks as $c)
                    <tr>
                        <td class="w-12 p-4 text-lg">{{ $c['ok'] ? '✅' : ($c['level'] === 'danger' ? '❌' : '⚠️') }}</td>
                        <td class="p-4 font-medium text-gray-900">{{ $c['label'] }}</td>
                        <td class="p-4 text-gray-600">{{ $c['detail'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="panel space-y-4 p-6">
            <h3 class="panel-title"><span class="ico"><x-icon name="shield" class="h-4 w-4" /></span> Sauvegardes</h3>
            @if ($last)
                <div class="text-sm text-gray-700">
                    Dernière : <strong class="text-gray-900">{{ $last['at']->format('d/m/Y H:i') }}</strong>
                    ({{ $last['at']->diffForHumans() }}) · {{ number_format($last['size'] / 1048576, 2, ',', ' ') }} Mo
                    · {{ $last['count'] }} archive(s) conservée(s) · disque « {{ $last['disk'] }} »
                </div>
            @else
                <div class="text-sm text-red-600">Aucune sauvegarde trouvée.</div>
            @endif

            <form method="POST" action="{{ route('system.backup') }}"
                  onsubmit="return confirm('Lancer une sauvegarde de la base maintenant ?');">
                @csrf
                <x-primary-button>Sauvegarder la base maintenant</x-primary-button>
            </form>
            <p class="text-xs text-gray-500">
                La sauvegarde complète (base + fichiers) est exécutée chaque nuit par le planificateur.
                Procédure de restauration : <code class="text-gold-400">docs/RESTAURATION.md</code>.
            </p>
        </div>
    </div>
</x-app-layout>

