<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2>{{ $report['title'] }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $report['subtitle'] }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('reports.index') }}" class="px-3 py-2 bg-gray-700 text-white rounded">← Rapports</a>
                <a href="{{ route('reports.export', ['type' => $type, 'format' => 'pdf'] + request()->query()) }}"
                   class="px-3 py-2 bg-red-600 text-white rounded">PDF</a>
                <a href="{{ route('reports.export', ['type' => $type, 'format' => 'xlsx'] + request()->query()) }}"
                   class="px-3 py-2 bg-green-600 text-white rounded">Excel</a>
            </div>
        </div>
    </x-slot>

    @php
        $cols  = $report['columns'];
        $max   = 500;
        $shown = array_slice($report['rows'], 0, $max);
        $right = fn ($c) => in_array($c['type'], ['money', 'number'], true) ? 'text-right' : '';
    @endphp

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-100 p-3 text-sm text-red-800">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <form method="GET" x-data="{ mode: '{{ $period['mode'] }}' }"
              class="panel flex flex-wrap items-center gap-3 p-3">
            @if ($meta['period'])
                <select name="mode" x-model="mode">
                    <option value="day">Journalier</option>
                    <option value="month">Mensuel</option>
                    <option value="year">Annuel</option>
                    <option value="custom">Période libre</option>
                </select>
                <input type="date" name="date" value="{{ $period['date'] }}" x-show="mode === 'day'">
                <input type="month" name="month" value="{{ $period['month'] }}" x-show="mode === 'month'">
                <input type="number" name="year" min="2000" max="2100" value="{{ $period['year'] }}" x-show="mode === 'year'" class="w-28">
                <span x-show="mode === 'custom'" class="flex gap-2">
                    <input type="date" name="from" value="{{ $period['from']->format('Y-m-d') }}">
                    <input type="date" name="to" value="{{ $period['to']->format('Y-m-d') }}">
                </span>
            @endif

            @if ($commerciaux->isNotEmpty())
                <select name="commercial_id">
                    <option value="">Tous les commerciaux</option>
                    @foreach ($commerciaux as $c)
                        <option value="{{ $c->id }}" @selected(request('commercial_id') == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            @endif

            @if ($meta['period'] || $commerciaux->isNotEmpty())
                <button class="rounded-lg bg-gold-500 px-5 py-2.5 text-sm font-semibold text-gray-950 hover:bg-gold-400">Afficher</button>
            @endif
        </form>

        @if (! empty($report['summary']))
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($report['summary'] as $s)
                    <x-stat-card :label="$s['label']"
                        :value="\App\Services\ReportService::fmt(['type' => $s['type'] ?? 'text'], $s['value']) . (($s['type'] ?? '') === 'money' ? ' DH' : '')"
                        icon="chart" />
                @endforeach
            </div>
        @endif

        <div class="panel overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left">
                    <tr>
                        @foreach ($cols as $c)
                            <th class="p-4 {{ $right($c) }}">{{ $c['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                @forelse ($shown as $row)
                    <tr>
                        @foreach ($cols as $c)
                            <td class="p-4 {{ $right($c) }}">{{ \App\Services\ReportService::fmt($c, $row[$c['key']] ?? null) }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($cols) }}" class="p-12 text-center text-gray-500">Aucune donnée sur cette période.</td></tr>
                @endforelse
                </tbody>
                @if (! empty($report['totals']) && count($report['rows']))
                    <tfoot>
                        <tr class="font-bold text-gold-400">
                            @foreach ($cols as $i => $c)
                                <td class="p-4 {{ $right($c) }}">
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