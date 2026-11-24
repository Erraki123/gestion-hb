@php
    $cols  = $report['columns'];
    $max   = 500;
    $shown = array_slice($report['rows'], 0, $max);
    $right = fn ($c) => in_array($c['type'], ['money', 'number'], true) ? 'text-right' : '';
    $hbtn  = 'inline-flex items-center gap-1 rounded-lg border border-gold-300 bg-gold-100 px-3 py-1.5 text-xs font-semibold text-gray-900 shadow-sm transition hover:bg-gold-200';

    // Périodes rapides (un clic)
    $t = today();
    $lm = $t->copy()->subMonthNoOverflow();
    $presets = [
        'Aujourd\'hui' => [['mode' => 'day', 'date' => $t->toDateString()], $t->copy(), $t->copy()],
        'Ce mois'      => [['mode' => 'month', 'month' => $t->format('Y-m')], $t->copy()->startOfMonth(), $t->copy()->endOfMonth()],
        'Mois dernier' => [['mode' => 'month', 'month' => $lm->format('Y-m')], $lm->copy()->startOfMonth(), $lm->copy()->endOfMonth()],
        'Ce trimestre' => [['mode' => 'quarter', 'quarter' => $t->quarter, 'year' => $t->year], $t->copy()->startOfQuarter(), $t->copy()->endOfQuarter()],
        'Cette année'  => [['mode' => 'year', 'year' => $t->year], $t->copy()->startOfYear(), $t->copy()->endOfYear()],
    ];
    $keep = request()->only('commercial_id');
    $isPreset = false;
    foreach ($presets as [$q, $pf, $pt]) {
        if ($period['from']->toDateString() === $pf->toDateString() && $period['to']->toDateString() === $pt->toDateString()) { $isPreset = true; }
    }
    $tone = ['warn' => 'text-orange-600', 'good' => 'text-green-700'];
@endphp
<x-app-layout dark-header>
    <x-slot name="header">
        <style>
            header, header > div { padding-top:.5rem !important; padding-bottom:.5rem !important; }
            .tight table { font-size:.8rem; }
            .tight th { font-size:.68rem; }
        </style>
        <div class="flex items-center justify-between gap-2">
            <div class="flex-1"><a href="{{ route('reports.index') }}" class="text-xs text-gray-300 hover:text-gold-300">← Rapports</a></div>
            <div class="text-center">
                <h2 class="justify-center !text-lg !leading-tight">{{ $report['title'] }}</h2>
                <p class="text-xs text-gray-500">{{ $report['subtitle'] }}</p>
            </div>
            <div class="flex flex-1 items-center justify-end gap-2">
                <a href="{{ route('reports.export', ['type' => $type, 'format' => 'pdf'] + request()->query()) }}" class="{{ $hbtn }}">PDF</a>
                <a href="{{ route('reports.export', ['type' => $type, 'format' => 'xlsx'] + request()->query()) }}" class="{{ $hbtn }}">Excel</a>
            </div>
        </div>
    </x-slot>

    <div class="py-5 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-100 p-3 text-sm text-red-800">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        {{-- PÉRIODE : boutons directs --}}
        @if ($meta['period'])
            <div class="flex flex-wrap items-center gap-2" x-data="{ other: {{ $period['mode'] === 'custom' ? 'true' : 'false' }} }">
                @foreach ($presets as $label => [$q, $pf, $pt])
                    @php $on = $period['from']->toDateString() === $pf->toDateString() && $period['to']->toDateString() === $pt->toDateString(); @endphp
                    <a href="{{ route('reports.show', ['type' => $type] + $q + $keep) }}"
                       class="{{ $on ? 'rounded-lg bg-gray-950 px-3 py-1.5 text-sm font-semibold text-gold-300' : 'rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gold-50' }}">{{ $label }}</a>
                @endforeach
                <button type="button" x-on:click="other = ! other"
                        class="{{ ! $isPreset ? 'rounded-lg bg-gray-950 px-3 py-1.5 text-sm font-semibold text-gold-300' : 'rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gold-50' }}">Autre période…</button>

                <form method="GET" x-show="other" x-cloak class="flex flex-wrap items-center gap-2">
                    <input type="hidden" name="mode" value="custom">
                    @if (request('commercial_id')) <input type="hidden" name="commercial_id" value="{{ request('commercial_id') }}"> @endif
                    <input type="date" name="from" value="{{ $period['from']->format('Y-m-d') }}">
                    <span class="text-gray-500">→</span>
                    <input type="date" name="to" value="{{ $period['to']->format('Y-m-d') }}">
                    <button class="rounded-lg bg-gold-500 px-4 py-2 text-sm font-semibold text-gray-950 hover:bg-gold-400">OK</button>
                </form>
            </div>
        @endif

        @if ($commerciaux->isNotEmpty())
            <form method="GET">
                @foreach (request()->except('commercial_id') as $k => $v)
                    @if (is_scalar($v)) <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endif
                @endforeach
                <select name="commercial_id" onchange="this.form.submit()">
                    <option value="">Tous les commerciaux</option>
                    @foreach ($commerciaux as $c)
                        <option value="{{ $c->id }}" @selected(request('commercial_id') == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </form>
        @endif

        {{-- LES GRANDS CHIFFRES --}}
        @if (! empty($report['summary']))
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                @foreach ($report['summary'] as $s)
                    <div class="panel p-3 text-center">
                        <div class="text-xs text-gray-500">{{ $s['label'] }}</div>
                        <div class="text-lg font-bold sm:text-2xl {{ $tone[$s['tone'] ?? ''] ?? 'text-gray-900' }}">
                            {{ \App\Services\ReportService::fmt(['type' => $s['type'] ?? 'text'], $s['value']) }}{{ ($s['type'] ?? '') === 'money' ? ' DH' : '' }}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- TABLEAU --}}
        <div class="panel tight overflow-x-auto">
            <table class="min-w-full">
                <thead class="text-left">
                    <tr>
                        @foreach ($cols as $c)
                            <th class="px-3 py-2 {{ $right($c) }}">{{ $c['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                @forelse ($shown as $row)
                    <tr class="border-t border-gray-100">
                        @foreach ($cols as $c)
                            <td class="px-3 py-2 {{ $right($c) }}">{{ \App\Services\ReportService::fmt($c, $row[$c['key']] ?? null) }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($cols) }}" class="p-8 text-center text-sm text-gray-500">Aucune donnée sur cette période.</td></tr>
                @endforelse
                </tbody>
                @if (! empty($report['totals']) && count($report['rows']))
                    <tfoot>
                        <tr class="border-t-2 border-gold-500 font-bold">
                            @foreach ($cols as $i => $c)
                                <td class="px-3 py-2 {{ $right($c) }}">
                                    @if ($i === 0) TOTAL
                                    @elseif (isset($report['totals'][$c['key']])) {{ \App\Services\ReportService::fmt($c, $report['totals'][$c['key']]) }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        @if (count($report['rows']) > $max)
            <p class="text-sm text-orange-700">Affichage limité aux {{ $max }} premières lignes sur {{ count($report['rows']) }}. Les exports contiennent tout.</p>
        @endif
        @if (! empty($report['note']))
            <p class="text-xs text-gray-500">{{ $report['note'] }}</p>
        @endif
    </div>
</x-app-layout>
