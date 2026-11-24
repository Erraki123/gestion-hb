@php
    $isAdmin = auth()->user()->isAdmin();
    $tabs = array_values(array_filter([
        $isAdmin ? ['label' => 'Commerciaux',  'url' => route('admin.commerciaux.index'), 'active' => request()->routeIs('admin.commerciaux.*')] : null,
        ['label' => 'Commissions', 'url' => route('commissions.index'),   'active' => request()->routeIs('commissions.index')],
        ['label' => 'Versements',  'url' => route('commissions.payouts'), 'active' => request()->routeIs('commissions.payouts*')],
    ]));
@endphp
<nav class="mb-4 flex gap-1 border-b border-gray-200" aria-label="Commerciaux et commissions">
    @foreach ($tabs as $t)
        <a href="{{ $t['url'] }}"
           @if ($t['active']) aria-current="page" @endif
           class="-mb-px border-b-2 px-4 py-2 text-sm font-semibold {{ $t['active'] ? 'border-gold-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-900' }}">
            {{ $t['label'] }}
        </a>
    @endforeach
</nav>
