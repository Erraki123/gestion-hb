#!/usr/bin/env python3
"""HN BLOOM invoice approval patch: conservative anchor-based modification."""
from pathlib import Path
import datetime, shutil, subprocess, sys

ROOT = Path.cwd()
DIR = Path(__file__).resolve().parent
if not (ROOT/'artisan').is_file():
    sys.exit('ERROR: Run inside your Laravel project directory.')

changes = {}

def read(path):
    if path not in changes:
        p = ROOT/path
        if not p.is_file(): raise RuntimeError('Missing project file: '+path)
        changes[path] = p.read_text(encoding='utf-8-sig')
    return changes[path]

def replace(path, old, new):
    s=read(path)
    if new in s: return
    if s.count(old) != 1:
        raise RuntimeError(f'Unsafe patch in {path}: expected one occurrence, found {s.count(old)} for {old[:75]!r}')
    changes[path]=s.replace(old,new,1)

def insert_before(path, needle, payload):
    s=read(path)
    if payload in s:return
    if s.count(needle)!=1:raise RuntimeError(f'Unsafe insertion in {path}: {needle[:65]!r}')
    changes[path]=s.replace(needle,payload+needle,1)

try:
    i='app/Http/Controllers/InvoiceController.php'
    # Commercial direct invoice must remain a draft. Admin direct issue keeps existing behavior.
    replace(i,"        // Création + émission dans UNE transaction", "        // Commercial: draft + mandatory director approval. Admin: direct issuance remains available.")
    replace(i,"            return $service->issueLocked($inv->id, auth()->id());", "            return $request->user()->isAdmin() ? $service->issueLocked($inv->id, auth()->id()) : $inv;")
    # Closure captures request, needed for admin check.
    replace(i, "use ($client, $built, $data, $due, $service, $transport, $installation, $grandTotal)",
            "use ($client, $built, $data, $due, $service, $transport, $installation, $grandTotal, $request)")
    replace(i, "        InvoiceIssued::dispatch($invoice);\n\n        if ($data['action'] === 'issue_pay') {", """        if (! $request->user()->isAdmin()) {
            app(\\App\\Services\\InvoiceIssueApproval::class)->request($invoice, $request->user()->id);
            return redirect()->route('invoices.show', $invoice)
                ->with('success', 'Brouillon enregistre. Validation du directeur demandee automatiquement.');
        }

        InvoiceIssued::dispatch($invoice);

        if ($data['action'] === 'issue_pay') {""")
    # Block commercial from attempting to register payment during draft creation.
    insert_before(i, "        // Le Global Scope renvoie 404", """        if (! $request->user()->isAdmin() && $data['action'] === 'issue_pay') {
            throw ValidationException::withMessages(['action' => 'La facture doit etre approuvee avant l enregistrement d un paiement. Enregistrez d abord sans paiement.']);
        }

""")
    # fromOrder; avoid an unapproved commercial invoice becoming issued or triggering auto-delivery.
    replace(i,"        if ($order->status === 'confirmee'", "        if (auth()->user()->isAdmin() && $order->status === 'confirmee'")
    replace(i, "            return $service->issueLocked($invoice->id, auth()->id());", "            return auth()->user()->isAdmin() ? $service->issueLocked($invoice->id, auth()->id()) : $invoice;")
    replace(i,"        InvoiceIssued::dispatch($invoice);\n\n        return redirect()->route('invoices.show', $invoice)\n            ->with('success', 'Facture ' . $invoice->number . ' créée.');", """        if (! auth()->user()->isAdmin()) {
            app(\\App\\Services\\InvoiceIssueApproval::class)->request($invoice, auth()->id());
            return redirect()->route('invoices.show', $invoice)
                ->with('success', 'Facture brouillon creee depuis la commande, en attente de validation du directeur.');
        }
        InvoiceIssued::dispatch($invoice);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Facture ' . $invoice->number . ' créée.');""")
    replace(i, "    public function issue(Invoice $invoice, InvoiceService $service)\n    {", """    public function issue(Invoice $invoice, InvoiceService $service)
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'Validation du directeur obligatoire.');""")
    # This existing endpoint must not let the commercial download a pending invoice.
    replace(i, "    public function pdf(Request $request, Invoice $invoice)\n    {", """    public function pdf(Request $request, Invoice $invoice)
    {
        abort_if($invoice->isDraft() && ! $request->user()->isAdmin(), 403, 'Facture en attente de validation.');""")

    # A formally approved invoice can be downloaded by the commercial after issuance.
    # This does NOT grant permission to send it to the client: Sprint C release gate is separate.
    current = read(i)
    e_guard = "abort_unless($request->user()->isAdmin() || app(\\App\\Services\\DocumentReleaseApproval::class)->canRelease($invoice), 403);"
    if e_guard in current:
        replace(i, e_guard, "abort_unless($request->user()->isAdmin() || app(\\App\\Services\\DocumentReleaseApproval::class)->canRelease($invoice) || (! $invoice->isDraft() && app(\\App\\Services\\InvoiceIssueApproval::class)->status($invoice) === 'approved'), 403);")

    # Enforce issuance permission at domain service layer; not just the controller.
    s='app/Services/InvoiceService.php'
    replace(s, "    public function issueLocked(int $invoiceId, int $userId): Invoice\n    {", """    public function issueLocked(int $invoiceId, int $userId): Invoice
    {
        abort_unless(\\App\\Models\\User::find($userId)?->isAdmin(), 403, 'Emission reservee au directeur.');""")
    # Admin cannot bypass a pending/rejected commercial invoice via a legacy payment or issue endpoint.
    insert_before(s, "        if ($i->type !== 'facture' || $i->status !== 'brouillon') {", """        $pendingDecision = DB::table('invoice_issue_approvals')->where('invoice_id', $i->id)->first();
        if ($pendingDecision && ($pendingDecision->status !== 'approved'
            || ! hash_equals((string) $pendingDecision->fingerprint, app(\\App\\Services\\InvoiceIssueApproval::class)->fingerprint($i)))) {
            throw ValidationException::withMessages(['approval' => 'Validation du directeur requise pour cette facture.']);
        }

""")

    # Request/decision routes: same auth group as invoice endpoints; decision additionally admin.
    route='routes/web.php'
    insert_before(route,"        Route::resource('invoices', InvoiceController::class)", """        Route::post('invoices/{invoice}/approval-request', [\\App\\Http\\Controllers\\InvoiceIssueApprovalController::class, 'request'])->name('invoices.approval.request');
        Route::post('invoices/{invoice}/approval-decision', [\\App\\Http\\Controllers\\InvoiceIssueApprovalController::class, 'decide'])->middleware('role:admin')->name('invoices.approval.decide');
""")

    # Keep the commercial invoice form simple: no premature pay-now option.
    form='resources/views/invoices/create.blade.php'
    replace(form, '                        <button type="button" @click="payNow = true"', '                        @if (auth()->user()->isAdmin())\n                        <button type="button" @click="payNow = true"')
    replace(form, '                        </button>\n                    </div>\n\n                    <div x-show="payNow"', '                        </button>\n                        @endif\n                    </div>\n\n                    <div x-show="payNow"')

    # Approval center list includes every pending commercial invoice, direct or order-backed.
    ctrl='app/Http/Controllers/Admin/ApprovalCenterController.php'
    insert_before(ctrl,"        return view('admin.approvals.index'", """        $pendingIssueIds = \\Illuminate\\Support\\Facades\\DB::table('invoice_issue_approvals')
            ->where('status', 'pending')->orderBy('requested_at')->limit(100)->pluck('invoice_id');
        $pendingIssueInvoices = \\App\\Models\\Invoice::with(['client', 'commercial', 'items', 'order'])
            ->whereIn('id', $pendingIssueIds)->where('type', 'facture')->where('status', 'brouillon')->get()
            ->filter(fn ($invoice) => app(\\App\\Services\\InvoiceIssueApproval::class)->status($invoice) === 'pending');
""")
    # Handle both source baseline and Sprint E version.
    old="return view('admin.approvals.index', compact('quotes', 'orders', 'counts', 'pendingInvoices'));"
    if old in read(ctrl):
        replace(ctrl,old,"return view('admin.approvals.index', compact('quotes', 'orders', 'counts', 'pendingInvoices', 'pendingIssueInvoices'));")
    else:
        replace(ctrl,"return view('admin.approvals.index', compact('quotes', 'orders', 'counts'));", "return view('admin.approvals.index', compact('quotes', 'orders', 'counts', 'pendingIssueInvoices'));")

    v='resources/views/admin/approvals/index.blade.php'
    block="""        {{-- HN BLOOM invoice issuance approval: direct and order-backed invoices. --}}
        <section class="overflow-hidden rounded-2xl border border-amber-200 bg-white shadow-sm">
            <div class="flex items-center justify-between gap-3 bg-gray-950 px-5 py-4 text-white">
                <h3 class="font-semibold">Factures a valider avant emission</h3>
                <span class="rounded-full border border-amber-500 px-3 py-1 text-xs text-amber-200">{{ $pendingIssueInvoices->count() }} en attente</span>
            </div>
            <div class="space-y-4 p-5">
                @forelse ($pendingIssueInvoices as $invoice)
                    <article class="rounded-xl border border-gray-200 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <strong class="text-gray-950">Brouillon #{{ $invoice->id }}</strong>
                                <p class="text-sm text-gray-600">{{ $invoice->client?->name }} · {{ $invoice->commercial?->name }} · {{ $invoice->order_id ? 'Depuis commande' : 'Facture directe' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-gray-950">{{ number_format((float)$invoice->total_ttc, 2, ',', ' ') }} DH</p>
                                <a class="text-sm font-semibold text-amber-800 underline" href="{{ route('invoices.show', $invoice) }}">Voir tous les details</a>
                                <a class="ml-3 text-sm font-semibold text-amber-800 underline" target="_blank" rel="noopener" href="{{ route('invoices.pdf', $invoice) }}">Apercu PDF (admin)</a>
                            </div>
                        </div>
                        <div class="mt-3 overflow-x-auto">
                            <table class="w-full text-left text-xs"><thead><tr><th class="p-2">Produit</th><th class="p-2">Qte</th><th class="p-2">Prix HT</th><th class="p-2">Remise</th></tr></thead><tbody>
                                @foreach($invoice->items as $line)
                                <tr class="border-t"><td class="p-2">{{ $line->name }}</td><td class="p-2">{{ $line->quantity }}</td><td class="p-2">{{ number_format((float)$line->unit_price, 2, ',', ' ') }}</td><td class="p-2">{{ $line->discount_percent }}%</td></tr>
                                @endforeach
                            </tbody></table>
                        </div>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <form action="{{ route('invoices.approval.decide', $invoice) }}" method="POST">@csrf
                                <input type="hidden" name="decision" value="approved">
                                <button class="w-full rounded-lg bg-green-700 p-2.5 text-sm font-semibold text-white">Approuver et emettre</button>
                            </form>
                            <form action="{{ route('invoices.approval.decide', $invoice) }}" method="POST" class="space-y-2">@csrf
                                <input type="hidden" name="decision" value="rejected">
                                <input type="text" name="comment" required maxlength="2000" placeholder="Motif du refus obligatoire" class="w-full rounded-lg border-gray-300 text-sm">
                                <button class="w-full rounded-lg border border-red-500 p-2 text-sm font-semibold text-red-700">Refuser</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <p class="text-sm text-gray-500">Aucune facture a valider.</p>
                @endforelse
            </div>
        </section>

"""
    insert_before(v,"</x-app-layout>",block)

    show='resources/views/invoices/show.blade.php'
    review="""        @if ($invoice->type === 'facture' && $invoice->isDraft())
            @php($issueState = app(\\App\\Services\\InvoiceIssueApproval::class)->status($invoice))
            <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950">
                <strong>Validation du directeur necessaire.</strong>
                <p class="mt-1">Statut : {{ $issueState === 'pending' ? 'En attente de validation' : ($issueState === 'rejected' ? 'Refuse : correction necessaire' : 'Demande a renouveler') }}. Aucun PDF final, paiement ou emission pour le commercial.</p>
                @if ($issueState !== 'pending')
                    <form class="mt-3" method="POST" action="{{ route('invoices.approval.request', $invoice) }}">@csrf
                        <button class="rounded-lg bg-gray-950 px-4 py-2 font-semibold text-amber-200">Demander une nouvelle validation</button>
                    </form>
                @endif
                @if (auth()->user()->isAdmin())
                    <a class="mt-2 inline-block font-semibold underline" href="{{ route('approvals.index') }}">Centre de validation</a>
                @endif
            </div>
        @endif
"""
    insert_before(show,"        @if (session('auto_pdf'))",review)
    # Hide 'print' menu for pending commercial; admin can inspect the draft PDF.
    replace(show,"                <div class=\"relative text-left\" x-data=\"{ open: false }\"", "                @if (! $invoice->isDraft() || auth()->user()->isAdmin())\n                <div class=\"relative text-left\" x-data=\"{ open: false }\"")
    replace(show,"                @if ($canReturn)", "                @endif\n\n                @if ($canReturn)")

    # Explanation on creation screen: no PDF/payment until director signoff.
    create='resources/views/invoices/create.blade.php'
    insert_before(create,"        <form method=\"POST\" action=\"{{ route('invoices.store') }}\"", """        @if (! auth()->user()->isAdmin())
            <div class="mb-4 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
                La facture sera enregistree en brouillon et transmise automatiquement au directeur. Paiement, emission et PDF final apres validation uniquement.
            </div>
        @endif
""")

    # Copy only the files authored in this sprint; don't overwrite previous patches.
    for p in ['app/Services/InvoiceIssueApproval.php', 'app/Http/Controllers/InvoiceIssueApprovalController.php', 'database/migrations/2026_11_15_190000_create_invoice_issue_approvals_table.php']:
        if (ROOT/p).exists():raise RuntimeError('File already exists; refusing unsafe overwrite: '+p)
        changes[p]=(DIR/'PATCH_INVOICE_APPROVAL'/p).read_text(encoding='utf-8')

    backup=ROOT/('backup-hnb-invoice-approval-'+datetime.datetime.now().strftime('%Y%m%d-%H%M%S'))
    backup.mkdir()
    written=[]
    try:
        for name,body in changes.items():
            dest=ROOT/name
            if dest.exists():
                bak=backup/name
                bak.parent.mkdir(parents=True,exist_ok=True)
                shutil.copy2(dest,bak)
            dest.parent.mkdir(parents=True,exist_ok=True)
            dest.write_text(body,encoding='utf-8')
            written.append(name)
        for name in written:
            if name.endswith('.php') and '/views/' not in '/'+name and not name.endswith('.blade.php'):
                t=subprocess.run(['php','-l',str(ROOT/name)],capture_output=True,text=True)
                print(t.stdout.strip())
                if t.returncode:raise RuntimeError(t.stderr)
        # Migrate after successful syntax validation. Never use --force or migrate:fresh.
        t=subprocess.run(['php','artisan','migrate','--path=database/migrations/2026_11_15_190000_create_invoice_issue_approvals_table.php','--no-interaction'],cwd=ROOT)
        if t.returncode:raise RuntimeError('Migration failed; source files will be restored.')
        for cmd in [['php','artisan','route:clear'],['php','artisan','view:cache']]:
            t=subprocess.run(cmd,cwd=ROOT)
            if t.returncode:raise RuntimeError('Command failed: '+' '.join(cmd))
    except Exception:
        for name in reversed(written):
            dest=ROOT/name;bak=backup/name
            if bak.exists():shutil.copy2(bak,dest)
            elif dest.exists():dest.unlink()
        raise
    print('INVOICE APPROVAL INSTALLED. Backup:',backup)
    print('Commercial invoices: DRAFT pending director; admin approves to ISSUE. No automatic send.')
except Exception as e:
    sys.exit('INSTALL ERROR: '+str(e))
