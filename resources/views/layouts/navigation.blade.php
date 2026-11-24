@php
    $admin    = auth()->user()->isAdmin();
    $hasLogo  = file_exists(public_path('logo.png'));

    $groups = [
        ['title' => null, 'items' => [
            ['Tableau de bord', route('dashboard'), 'dashboard', request()->routeIs('dashboard', 'admin.dashboard', 'commercial.dashboard')],
        ]],
        ['title' => 'Ventes', 'items' => [
            ['Clients',   route('clients.index'),  'users',   request()->routeIs('clients.*')],
            ['Prospection', route('prospects.index'), 'users', request()->routeIs('prospects.*')],
            ['Mon agenda IA', route('hnb.routines.index'), 'clock', request()->routeIs('hnb.routines.*')],
            ['Email', route('hnb.email.index'), 'file', request()->routeIs('hnb.email.*')],
            ['WhatsApp', route('whatsapp.assistant.index'), 'file', request()->routeIs('whatsapp.assistant.*', 'admin.whatsapp.*')],
            ['Commandes', route('quotes.index'),   'cart',    request()->routeIs('quotes.*', 'orders.*', 'deliveries.*')],
            ['Factures',  route('invoices.index'), 'receipt', request()->routeIs('invoices.*', 'payments.*', 'receivables.*', 'returns.*')],
        ]],
    ];

    $gestion = [
        ['Produits', route('products.index'), 'package', request()->routeIs('products.*', 'categories.*')],
    ];
    if ($admin) {
        $gestion[] = ['Achats', route('purchases.index'), 'clipboard', request()->routeIs('purchases.*', 'suppliers.*', 'purchase-orders.*', 'supplier-invoices.*', 'receipts.*')];
    }
    $gestion[] = ['Rapports', route('reports.index'), 'chart', request()->routeIs('reports.*')];
    $gestion[] = $admin
        ? ['Commerciaux',      route('admin.commerciaux.index'), 'briefcase', request()->routeIs('admin.commerciaux.*', 'commissions.*')]
        : ['Mes commissions',  route('commissions.index'),       'percent',   request()->routeIs('commissions.*')];

    $groups[] = ['title' => 'Gestion', 'items' => $gestion];

    if ($admin) {
        $groups[] = ['title' => 'Administration', 'items' => [
            ['Notifications', route('admin.direction.notifications.index'), 'clock', request()->routeIs('admin.direction.notifications.*')],
            ['Validations',         route('admin.approvals.index'), 'shield', request()->routeIs('admin.approvals.*')],
            ['Paramètres',          route('settings.edit'),    'settings', request()->routeIs('settings.*')],
            ['Corbeille',           route('trash.index'),      'trash',    request()->routeIs('trash.*')],
            ['Journal d\'activité', route('activity.index'),   'activity', request()->routeIs('activity.*')],
            ['Export comptable',    route('accounting.index'), 'download', request()->routeIs('accounting.*')],
            ['Système',             route('system.index'),     'shield',   request()->routeIs('system.*')],
        ]];
    }
@endphp

{{-- Logo --}}
<div class="flex h-14 shrink-0 items-center border-b border-white/10 px-5">
    <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3" aria-label="{{ config('commerce.company.name') }}">
        @if ($hasLogo)
            <img src="{{ asset('logo.png') }}" alt="{{ config('commerce.company.name') }}" class="h-9 w-auto max-w-[11rem] object-contain">
        @else
            <x-brand-mark class="h-8 w-8 shrink-0" />
            <span class="truncate text-sm font-semibold text-white">{{ config('commerce.company.name') }}</span>
        @endif
    </a>
</div>

{{-- Navigation --}}
<nav class="flex-1 overflow-y-auto px-3 py-3" aria-label="Navigation principale">
    <div class="space-y-4">
        @foreach ($groups as $g)
            <div>
                @if ($g['title'])
                    <div class="mb-1 px-3 text-[10px] font-semibold uppercase tracking-widest text-gray-500">{{ $g['title'] }}</div>
                @endif

                <div class="space-y-0.5">
                    @foreach ($g['items'] as [$label, $url, $icon, $active])
                        <a href="{{ $url }}" @click="sidebar = false"
                           @if ($active) aria-current="page" @endif
                           class="group flex items-center gap-3 rounded-r-lg border-l-2 px-3 py-2 text-sm transition
                                  {{ $active
                                      ? 'border-gold-500 bg-white/10 font-semibold text-white'
                                      : 'border-transparent text-gray-400 hover:bg-white/5 hover:text-white' }}">
                            <x-icon :name="$icon" class="h-[18px] w-[18px] shrink-0 {{ $active ? 'text-gold-300' : 'text-gray-500 group-hover:text-gold-300' }}" />
                            <span class="truncate">{{ $label }}</span>
                            @if ($admin && $label === 'Validations')
                                @php
                                    $approvalAlerts = \App\Models\Quote::where('approval_status', 'pending')->count()
                                        + \App\Models\Order::where('approval_status', 'pending')->count();
                                @endphp
                                @if ($approvalAlerts > 0)
                                    <span class="ml-auto rounded-full bg-amber-400 px-2 py-0.5 text-[10px] font-bold text-gray-950" aria-label="Demandes à valider">{{ $approvalAlerts }}</span>
                                @endif
                            @endif
                            @if ($admin && $label === 'Prospection')
                                @php
                                    $lateFollowUps = \App\Models\ProspectDetail::where('do_not_contact', false)->where('next_follow_up_at','<=',now())->whereNotIn('stage',['gagne','perdu'])->count();
                                @endphp
                                @if ($lateFollowUps > 0)
                                    <span class="ml-auto rounded-full bg-amber-400 px-2 py-0.5 text-[10px] font-bold text-gray-950" aria-label="Relances à traiter">{{ $lateFollowUps }}</span>
                                @endif
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</nav>

{{-- Profil --}}
<div class="shrink-0 border-t border-white/10 p-3">
    <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-lg p-2 transition hover:bg-white/5">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gold-500 text-xs font-semibold text-black">
            {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 2)) }}
        </span>
        <span class="min-w-0 leading-tight">
            <span class="block truncate text-sm font-medium text-white">{{ auth()->user()->name }}</span>
            <span class="block text-[11px] text-gray-500">{{ $admin ? 'Administrateur' : 'Commercial' }}</span>
        </span>
    </a>
</div>