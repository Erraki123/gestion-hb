@php
    $admin = auth()->user()->isAdmin();

    $groups = [
        ['title' => null, 'items' => [
            ['Tableau de bord', route('dashboard'), 'dashboard', request()->routeIs('dashboard', 'admin.dashboard', 'commercial.dashboard')],
        ]],
        ['title' => 'Ventes', 'items' => [
            ['Clients',    route('clients.index'),    'users',   request()->routeIs('clients.*')],
            ['Devis',      route('quotes.index'),     'file',    request()->routeIs('quotes.*')],
            ['Commandes',  route('orders.index'),     'cart',    request()->routeIs('orders.*')],
            ['Livraisons', route('deliveries.index'), 'truck',   request()->routeIs('deliveries.*')],
            ['Factures',   route('invoices.index'),   'receipt', request()->routeIs('invoices.*')],
            ['Retours',    route('returns.index'),    'undo',    request()->routeIs('returns.*', 'invoices.returns.*')],
        ]],
        ['title' => 'Finance', 'items' => [
            ['Paiements',   route('payments.index'),    'wallet',  request()->routeIs('payments.*')],
            ['Créances',    route('receivables.index'), 'clock',   request()->routeIs('receivables.*')],
            ['Commissions', route('commissions.index'), 'percent', request()->routeIs('commissions.*')],
        ]],
        ['title' => 'Catalogue', 'items' => [
            ['Produits', route('products.index'), 'package', request()->routeIs('products.*', 'categories.*')],
        ]],
        ['title' => 'Analyse', 'items' => [
            ['Rapports', route('reports.index'), 'chart', request()->routeIs('reports.*')],
        ]],
    ];

    if ($admin) {
        $groups[] = ['title' => 'Achats', 'items' => [
            ['Fournisseurs', route('suppliers.index'), 'building', request()->routeIs('suppliers.*')],
            ['Commandes fournisseur', route('purchase-orders.index'), 'clipboard', request()->routeIs('purchase-orders.*')],
            ['Factures fournisseur', route('supplier-invoices.index'), 'receipt', request()->routeIs('supplier-invoices.*')],
        ]];
        $groups[] = ['title' => 'Administration', 'items' => [
            ['Commerciaux',         route('admin.commerciaux.index'),  'briefcase', request()->routeIs('admin.commerciaux.*')],
            ['Règles commission',   route('commission-rules.index'),   'sliders',   request()->routeIs('commission-rules.*')],
            ['Paramètres',          route('settings.edit'),            'settings',  request()->routeIs('settings.*')],
            ['Corbeille',           route('trash.index'),              'trash',     request()->routeIs('trash.*')],
            ['Journal d\'activité', route('activity.index'),           'activity',  request()->routeIs('activity.*')],
            ['Export comptable',    route('accounting.index'),         'download',  request()->routeIs('accounting.*')],
            ['Système',             route('system.index'),             'shield',    request()->routeIs('system.*')],
        ]];
    }
@endphp

<div class="flex h-16 shrink-0 items-center gap-3 border-b border-white/5 px-5">
    <x-brand-mark class="h-9 w-9 shrink-0" />
    <div class="min-w-0 leading-tight">
        <div class="truncate font-semibold text-white">{{ config('commerce.company.name') }}</div>
        <div class="text-[10px] font-medium uppercase tracking-[.2em] text-gold-300">Gestion commerciale</div>
    </div>
</div>

<nav class="flex-1 space-y-5 overflow-y-auto px-3 py-5">
    @foreach ($groups as $g)
        <div>
            @if ($g['title'])
                <div class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-widest text-gray-500">{{ $g['title'] }}</div>
            @endif

            <div class="space-y-1">
                @foreach ($g['items'] as [$label, $url, $icon, $active])
                    <a href="{{ $url }}" @click="sidebar = false"
                       class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition
                              {{ $active
                                  ? 'bg-gradient-to-r from-gold-500 to-gold-600 font-medium text-white shadow-lg shadow-gold-500/30'
                                  : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                        <x-icon :name="$icon" class="h-5 w-5 {{ $active ? 'text-white' : 'text-gray-500 group-hover:text-gold-300' }}" />
                        <span class="truncate">{{ $label }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endforeach
</nav>

<div class="shrink-0 border-t border-white/5 p-3">
    <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-xl p-2 transition hover:bg-white/5">
        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-gold-500 to-orange-500 text-sm font-semibold text-white">
            {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 2)) }}
        </span>
        <span class="min-w-0 leading-tight">
            <span class="block truncate text-sm font-semibold text-white">{{ auth()->user()->name }}</span>
            <span class="block text-[11px] text-gray-400">{{ $admin ? 'Administrateur' : 'Commercial' }}</span>
        </span>
    </a>
</div>