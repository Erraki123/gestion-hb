<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #222; }
    h1 { font-size: 15px; margin: 0; }
    h2 { font-size: 13px; margin: 4px 0 0; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #ccc; padding: 3px 4px; }
    th { background: #f0f0f0; text-align: left; }
    .right { text-align: right; }
    .total td { font-weight: bold; background: #f7f7f7; }
    .summary td { border: none; padding: 2px 6px 2px 0; }
    .note { margin-top: 8px; color: #666; font-size: 8px; }
    .footer { position: fixed; bottom: 0; left: 0; right: 0; font-size: 8px; text-align: center; color: #666; }
</style>
</head>
<body>
    <h1>{{ config('commerce.company.name') }}</h1>
    <h2>{{ $report['title'] }}</h2>
    <div>{{ $report['subtitle'] }}</div>

    @if (! empty($report['summary']))
        <table class="summary" style="width:auto">
            @foreach ($report['summary'] as $s)
                <tr>
                    <td>{{ $s['label'] }} :</td>
                    <td><strong>{{ \App\Services\ReportService::fmt(['type' => $s['type'] ?? 'text'], $s['value']) }}{{ ($s['type'] ?? '') === 'money' ? ' DH' : '' }}</strong></td>
                </tr>
            @endforeach
        </table>
    @endif

    <table>
        <thead>
            <tr>
                @foreach ($report['columns'] as $c)
                    <th class="{{ in_array($c['type'], ['money', 'number'], true) ? 'right' : '' }}">{{ $c['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
        @foreach ($report['rows'] as $row)
            <tr>
                @foreach ($report['columns'] as $c)
                    <td class="{{ in_array($c['type'], ['money', 'number'], true) ? 'right' : '' }}">{{ \App\Services\ReportService::fmt($c, $row[$c['key']] ?? null) }}</td>
                @endforeach
            </tr>
        @endforeach
        @if (! empty($report['totals']) && count($report['rows']))
            <tr class="total">
                @foreach ($report['columns'] as $i => $c)
                    <td class="{{ in_array($c['type'], ['money', 'number'], true) ? 'right' : '' }}">
                        @if ($i === 0) TOTAL
                        @elseif (isset($report['totals'][$c['key']])) {{ \App\Services\ReportService::fmt($c, $report['totals'][$c['key']]) }}
                        @endif
                    </td>
                @endforeach
            </tr>
        @endif
        </tbody>
    </table>

    @if (! empty($report['note'])) <div class="note">{{ $report['note'] }}</div> @endif

    <div class="footer">Généré le {{ $report['generated'] }}</div>
</body>
</html>