@php $a = \App\Support\ChequeAlerts::upcoming(3, 10); @endphp
@if ($a['total'] > 0)
    <div class="panel overflow-hidden {{ $a['urgent'] > 0 ? 'border-red-300' : 'border-yellow-300' }}">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 bg-gold-50 px-4 py-3">
            <h3 class="panel-title">🔔 Chèques à surveiller ({{ $a['total'] }})</h3>
            <span class="text-sm font-semibold text-gray-900">{{ number_format($a['sum'], 2, ',', ' ') }} DH
                <span class="font-normal text-gray-500">· échéance ≤ 3 jours ou dépassée</span></span>
        </div>
        <x-cheque-list :items="$a['items']" />
    </div>
@endif
