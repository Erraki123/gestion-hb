@php
    $colors = [
        'created' => 'bg-green-100 text-green-800', 'updated' => 'bg-blue-100 text-blue-800',
        'deleted' => 'bg-red-100 text-red-800', 'restored' => 'bg-indigo-100 text-indigo-800',
        'login' => 'bg-gray-100 text-gray-700', 'logout' => 'bg-gray-100 text-gray-700',
        'login_failed' => 'bg-orange-100 text-orange-800', 'lockout' => 'bg-red-200 text-red-900',
        'export' => 'bg-yellow-100 text-yellow-800',
    ];
    $links = [
        \App\Models\Client::class => 'clients.show', \App\Models\Product::class => 'products.show',
        \App\Models\Quote::class => 'quotes.show', \App\Models\Order::class => 'orders.show',
        \App\Models\Invoice::class => 'invoices.show', \App\Models\CommissionPayout::class => 'commissions.payouts.show',
    ];
    $hasDetails = ! empty($log->properties);
    $showIp = $showIp ?? false;
@endphp

<tbody x-data="{ open: false }" class="border-t">
    <tr>
        <td class="p-4 whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
        <td class="p-4">{{ $log->user_name ?? $log->user?->name ?? 'Système' }}</td>
        <td class="p-4">
            <span class="px-2 py-1 rounded text-xs {{ $colors[$log->action] ?? 'bg-gray-100' }}">{{ $log->actionLabel() }}</span>
        </td>
        <td class="p-4">
            {{ $log->description }}
            @if ($log->subject_id && isset($links[$log->subject_type]) && $log->action !== 'deleted')
                <a href="{{ route($links[$log->subject_type], $log->subject_id) }}" class="act">↗</a>
            @endif
        </td>
        @if ($showIp) <td class="p-4 text-xs text-gray-500">{{ $log->ip }}</td> @endif
        <td class="p-4">
            @if ($hasDetails)
                <button type="button" @click="open = !open" class="act" x-text="open ? 'Masquer' : 'Détails'"></button>
            @endif
        </td>
    </tr>
    @if ($hasDetails)
        <tr x-show="open" style="display:none">
            <td colspan="{{ $showIp ? 6 : 5 }}" class="p-4 bg-black/20">@include('activity._props')</td>
        </tr>
    @endif
</tbody>