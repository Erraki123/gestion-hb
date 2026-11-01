<div class="space-y-3">
    <form method="GET" class="panel flex flex-wrap items-center gap-2 p-3">
        @if (! empty($cid)) <input type="hidden" name="commercial_id" value="{{ $cid }}"> @endif

        @foreach (['today' => "Aujourd'hui", 'month' => 'Ce mois', 'year' => 'Cette année'] as $k => $l)
            <a href="{{ request()->fullUrlWithQuery(['period' => $k, 'from' => null, 'to' => null]) }}"
               class="chip {{ $period['p'] === $k ? 'chip-on' : '' }}">{{ $l }}</a>
        @endforeach

        <span class="mx-1 hidden h-6 w-px bg-white/10 sm:block"></span>

        <input type="hidden" name="period" value="custom">
        <input type="date" name="from" value="{{ $period['from']->format('Y-m-d') }}" class="rounded-lg text-sm">
        <span class="text-gray-500">→</span>
        <input type="date" name="to" value="{{ $period['to']->format('Y-m-d') }}" class="rounded-lg text-sm">
        <button class="rounded-lg bg-gold-500 px-4 py-2 text-sm font-semibold text-gray-950 hover:bg-gold-400">Appliquer</button>

        <span class="ml-auto inline-flex items-center gap-2 rounded-lg border border-gold-500/30 bg-gold-500/10 px-3 py-1.5 text-xs text-gold-400">
            <x-icon name="clock" class="h-4 w-4" /> {{ $period['label'] }}
        </span>
    </form>

    @isset($commerciaux)
        <form method="GET" class="flex items-center gap-3">
            <input type="hidden" name="period" value="{{ $period['p'] }}">
            <input type="hidden" name="from" value="{{ $period['from']->format('Y-m-d') }}">
            <input type="hidden" name="to" value="{{ $period['to']->format('Y-m-d') }}">
            <x-icon name="briefcase" class="h-4 w-4 text-gold-500" />
            <select name="commercial_id" onchange="this.form.submit()" class="rounded-lg text-sm">
                <option value="">Tous les commerciaux</option>
                @foreach ($commerciaux as $c)
                    <option value="{{ $c->id }}" @selected(($cid ?? null) == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </form>
    @endisset
</div>