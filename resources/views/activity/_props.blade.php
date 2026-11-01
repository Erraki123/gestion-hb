@php
    $p    = $log->properties ?? [];
    $keys = array_values(array_unique(array_merge(array_keys($p['before'] ?? []), array_keys($p['after'] ?? []))));
    $val  = fn ($v) => is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE)
            : (is_bool($v) ? ($v ? 'oui' : 'non') : (($v === null || $v === '') ? '—' : $v));
@endphp

@if ($keys)
    <table class="text-xs w-full">
        <thead class="text-left text-gray-500">
            <tr><th class="py-1 pr-3">Champ</th><th class="py-1 pr-3">Avant</th><th class="py-1">Après</th></tr>
        </thead>
        <tbody>
        @foreach ($keys as $k)
            <tr class="border-t border-white/10 align-top">
                <td class="py-1 pr-3 font-medium">{{ $k }}</td>
                <td class="py-1 pr-3 text-red-700 break-all">{{ $val($p['before'][$k] ?? null) }}</td>
                <td class="py-1 text-green-700 break-all">{{ $val($p['after'][$k] ?? null) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

@if (! empty($p['data']))
    <div class="text-xs space-y-1">
        @foreach ($p['data'] as $k => $v)
            <div><span class="font-medium">{{ $k }} :</span> {{ $val($v) }}</div>
        @endforeach
    </div>
@endif