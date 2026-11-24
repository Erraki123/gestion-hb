{{-- Formulaire caché pour le filtre commercial (le select est dans la barre ci-dessous) --}}
@isset($commerciaux)
    <form id="commForm" method="GET" class="hidden">
        <input type="hidden" name="period" value="{{ $period['p'] }}">
        <input type="hidden" name="from" value="{{ $period['from']->format('Y-m-d') }}">
        <input type="hidden" name="to" value="{{ $period['to']->format('Y-m-d') }}">
    </form>
@endisset

<form method="GET" class="flex flex-wrap items-center gap-2 rounded-xl border border-gray-200 bg-white p-3 shadow-sm">
    @if (! empty($cid)) <input type="hidden" name="commercial_id" value="{{ $cid }}"> @endif

    @foreach (['today' => "Aujourd'hui", 'month' => 'Ce mois', 'year' => 'Cette année'] as $k => $l)
        <a href="{{ request()->fullUrlWithQuery(['period' => $k, 'from' => null, 'to' => null]) }}"
           class="rounded-lg px-3 py-1.5 text-sm font-medium {{ $period['p'] === $k ? 'bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-100' }}">{{ $l }}</a>
    @endforeach

    <span class="mx-1 hidden h-6 w-px bg-gray-200 sm:block"></span>

    <input type="hidden" name="period" value="custom">
    <input type="date" name="from" value="{{ $period['from']->format('Y-m-d') }}" class="rounded-lg border-gray-300 py-1.5 text-sm">
    <span class="text-gray-400">→</span>
    <input type="date" name="to" value="{{ $period['to']->format('Y-m-d') }}" class="rounded-lg border-gray-300 py-1.5 text-sm">
    <button class="rounded-lg bg-gray-900 px-4 py-1.5 text-sm font-medium text-white hover:bg-gray-700">Appliquer</button>

    @isset($commerciaux)
        <select form="commForm" name="commercial_id" onchange="this.form.submit()" class="ml-auto rounded-lg border-gray-300 py-1.5 text-sm">
            <option value="">Tous les commerciaux</option>
            @foreach ($commerciaux as $c)
                <option value="{{ $c->id }}" @selected(($cid ?? null) == $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
    @endisset
</form>