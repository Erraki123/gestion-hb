{{-- Tableau de bord : toutes les alarmes de transport actives (échues en premier) --}}
@php $transportItems = \App\Support\TransportAlerts::active(); @endphp

@if (count($transportItems) > 0)
    <div class="panel mb-6 overflow-hidden border-red-200">
        <div class="flex items-center justify-between border-b border-gray-200 bg-gold-50 px-4 py-3">
            <h3 class="panel-title">🚚 Transport à suivre</h3>
            <span class="text-xs text-gray-500">{{ count($transportItems) }} alarme(s) active(s)</span>
        </div>
        <x-transport-list :items="$transportItems" />
    </div>
@endif
