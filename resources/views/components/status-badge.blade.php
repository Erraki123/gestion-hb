@props(['status'])
@php
    $map = [
        'brouillon'           => ['Brouillon', 'bg-gray-200 text-gray-700'],
        'envoye'              => ['Envoyé', 'bg-blue-100 text-blue-800'],
        'accepte'             => ['Accepté', 'bg-green-100 text-green-800'],
        'refuse'              => ['Refusé', 'bg-red-100 text-red-800'],
        'expire'              => ['Expiré', 'bg-yellow-100 text-yellow-800'],
        'nouvelle'            => ['Nouvelle', 'bg-blue-100 text-blue-800'],
        'confirmee'           => ['Confirmée', 'bg-indigo-100 text-indigo-800'],
        'terminee'            => ['Terminée', 'bg-green-100 text-green-800'],
        'annulee'             => ['Annulée', 'bg-red-100 text-red-800'],
        'emise'               => ['Émise', 'bg-indigo-100 text-indigo-800'],
        'valide'     => ['Valide', 'bg-green-100 text-green-800'],
'annule'     => ['Annulé', 'bg-red-100 text-red-800'],
'en_attente' => ['En attente', 'bg-yellow-100 text-yellow-800'],
'encaisse'   => ['Encaissé', 'bg-green-100 text-green-800'],
'rejete'     => ['Rejeté', 'bg-red-200 text-red-900'],
'versee' => ['Versée', 'bg-green-100 text-green-800'],
        'partiellement_payee' => ['Partiellement payée', 'bg-orange-100 text-orange-800'],
        'payee'               => ['Payée', 'bg-green-100 text-green-800'],
        'en_retard'           => ['En retard', 'bg-red-200 text-red-900 font-semibold'],
    ];
    [$label, $classes] = $map[$status] ?? [ucfirst($status), 'bg-gray-100'];
@endphp
<span class="px-2 py-1 rounded text-xs {{ $classes }}">{{ $label }}</span>