@props(['status', 'type' => 'order'])
@php
    $maps = [
        'order' => [
            'brouillon'       => ['Brouillon', 'gray'],
            'commande'        => ['Commandée', 'blue'],
            'recue_partielle' => ['Reçue partiellement', 'orange'],
            'recue'           => ['Reçue', 'green'],
            'annulee'         => ['Annulée', 'red'],
        ],
        'invoice' => [
            'non_payee'           => ['À payer', 'orange'],
            'partiellement_payee' => ['Partiellement payée', 'orange'],
            'payee'               => ['Payée', 'green'],
            'annulee'             => ['Annulée', 'red'],
        ],
        'receipt' => [
            'brouillon' => ['Brouillon', 'gray'],
            'valide'    => ['Validée', 'green'],
            'annulee'   => ['Annulée', 'red'],
            'annule'    => ['Annulée', 'red'],
        ],
    ];
    [$l, $c] = $maps[$type][$status] ?? [ucfirst((string) $status), 'gray'];
    $cl = [
        'gray' => 'bg-gray-100 text-gray-700', 'blue' => 'bg-blue-100 text-blue-800',
        'orange' => 'bg-orange-100 text-orange-800', 'green' => 'bg-green-100 text-green-800',
        'red' => 'bg-red-100 text-red-800',
    ][$c];
@endphp
<span class="inline-flex whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-semibold {{ $cl }}">{{ $l }}</span>
