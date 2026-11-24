from pathlib import Path
from datetime import datetime
import shutil, subprocess, sys
ROOT=Path(__file__).resolve().parent
BACKUP=ROOT/('backup-final-sprint-e-'+datetime.now().strftime('%Y%m%d-%H%M%S'))
changes={}
def mod(path, old, new, *, count=1):
    p=ROOT/path
    if not p.exists(): raise RuntimeError('Missing expected file: '+path)
    s=changes.get(path,p.read_text(encoding='utf-8-sig'))
    if new in s: return
    if s.count(old)!=count: raise RuntimeError('Unexpected source for '+path+'; anchor count '+str(s.count(old))+' expected '+str(count)+'. Nothing written.')
    changes[path]=s.replace(old,new,count)

try:
    q='app/Http/Controllers/QuoteController.php'
    mod(q, "'requires_approval' => app(\\App\\Services\\QuotePriceApproval::class)->isRequired($built['lines']),\n                'approval_status' => app(\\App\\Services\\QuotePriceApproval::class)->isRequired($built['lines']) ? 'pending' : null,", "'requires_approval' => true,\n                'approval_status' => 'pending',\n                'approval_requested_by' => auth()->id(),\n                'approval_requested_at' => now(),")
    mod(q,"$sensitive = app(\\App\\Services\\QuotePriceApproval::class)->isRequired($built['lines']);", "$sensitive = true;")
    mod(q,"'approval_requested_by' => null, 'approval_requested_at' => null,", "'approval_requested_by' => auth()->id(), 'approval_requested_at' => now(),")
    mod(q,"'approval_status' => $sensitive ? 'pending' : null,", "'approval_status' => 'pending',")
    mod(q,"if (! $q->canSendToClient()) {", "if ($q->approval_status !== 'approved') {")
    # Existing price-based wording replaced with universal approval requirement
    mod(q,"Ce devis avec remise ou prix spécial doit être approuvé avant conversion.", "Ce devis doit etre approuve par le directeur avant conversion.")
    # Quote PDF endpoint declared in routes but missing from archived controller: safe admin preview / approved commercial download.
    s=changes.get(q,(ROOT/q).read_text(encoding='utf-8-sig'))
    if 'public function pdf(' not in s:
        anchor='    public function convert(Quote $quote, DocumentNumberService $numbers, StockService $stock)'
        if anchor not in s: raise RuntimeError('QuoteController convert anchor missing')
        method='''    public function pdf(Request $request, Quote $quote)
    {
        // Directeur may review any quote; commercial cannot access unapproved PDF.
        abort_unless($request->user()->isAdmin() || $quote->approval_status === 'approved', 403);
        $quote->load(['client', 'commercial', 'items', 'order']);
        $name = ($quote->number ?: 'devis-'.$quote->id).'.pdf';
        $pdf = Pdf::loadView('quotes.pdf', compact('quote'))->setPaper('a4');
        return $request->boolean('download') ? $pdf->download($name) : $pdf->stream($name);
    }

'''
        changes[q]=s.replace(anchor,method+anchor,1)
    # Core quote model guard affects UI and controllers, conversion also has server guard above.
    mod('app/Models/Quote.php',"return ! $this->requires_approval || $this->approval_status === 'approved';", "return $this->approval_status === 'approved';")
    mod('app/Models/Quote.php',"public function canConvert(): bool { return $this->isOpen(); }", "public function canConvert(): bool { return $this->isOpen() && $this->approval_status === 'approved'; }")
    # Center displays full quote line details and direct preview before decisions.
    c='app/Http/Controllers/Admin/ApprovalCenterController.php'
    mod(c,"$quotes = Quote::query()\n            ->with(['client', 'commercial'])", "$quotes = Quote::query()\n            ->with(['client', 'commercial', 'items'])")
    # Existing Sprint C approval requests for invoices become visible in the admin approval center.
    mod(c,"return view('admin.approvals.index', compact('quotes', 'orders', 'counts'));", r"""$invoiceRequestIds = \Illuminate\Support\Facades\DB::table('document_release_approvals')
            ->where('document_type', 'invoice')->where('status', 'pending')
            ->orderByDesc('id')->limit(100)->pluck('document_id')->unique()->values()->all();
        $pendingInvoices = \App\Models\Invoice::whereIn('id', $invoiceRequestIds)
            ->with(['client', 'items'])->get()
            ->filter(fn ($invoice) => app(\App\Services\DocumentReleaseApproval::class)->status($invoice) === 'pending');
        return view('admin.approvals.index', compact('quotes', 'orders', 'counts', 'pendingInvoices'));""")

    view='resources/views/admin/approvals/index.blade.php'
    mod(view,'Devis sensibles et confirmations de commandes à contrôler.', 'Tous les devis et confirmations de commandes à contrôler.')
    mod(view,'Devis sensibles en attente','Devis en attente de validation')
    anchor='''<div class="mt-4 grid gap-3 md:grid-cols-2">
                            <form method="POST" action="{{ route('quotes.approval.decide', $quote) }}">'''
    section='''<div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm text-gray-800">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <strong>Detail du devis avant decision</strong>
                                <div class="flex flex-wrap gap-3">
                                    <a href="{{ route('quotes.show', $quote) }}" class="font-semibold text-amber-800 underline">Voir le devis complet</a>
                                    <a href="{{ route('quotes.pdf', $quote) }}" target="_blank" rel="noopener" class="font-semibold text-amber-800 underline">Previsualiser PDF</a>
                                </div>
                            </div>
                            <p class="mt-2">Client : {{ $quote->client?->name ?? '—' }} | Commercial : {{ $quote->commercial?->name ?? '—' }}</p>
                            <div class="mt-2 overflow-x-auto"><table class="w-full text-left text-xs"><thead><tr><th class="px-2 py-1">Produit / Service</th><th class="px-2 py-1">Qte</th><th class="px-2 py-1">Prix HT</th><th class="px-2 py-1">Remise</th><th class="px-2 py-1">TVA</th></tr></thead><tbody>
                            @foreach ($quote->items as $item)
                                <tr class="border-t border-gray-200"><td class="px-2 py-1">{{ $item->name }}</td><td class="px-2 py-1">{{ $item->quantity }}</td><td class="px-2 py-1">{{ number_format((float) $item->unit_price, 2, ',', ' ') }} DH</td><td class="px-2 py-1">{{ $item->discount_percent }}%</td><td class="px-2 py-1">{{ $item->tax_rate }}%</td></tr>
                            @endforeach
                            </tbody></table></div>
                            <p class="mt-2 text-right font-semibold">HT : {{ number_format((float)$quote->subtotal_ht, 2, ',', ' ') }} DH | TVA : {{ number_format((float)$quote->tax_amount, 2, ',', ' ') }} DH | TTC : {{ number_format((float)$quote->total_ttc, 2, ',', ' ') }} DH</p>
                            @if ($quote->terms || $quote->notes)<p class="mt-2 text-xs">Conditions : {{ $quote->terms }} {{ $quote->notes }}</p>@endif
                        </div>
                        '''
    mod(view,anchor,section+anchor,count=1)
    invoiceSection='''<section class="mx-auto my-6 max-w-7xl rounded-2xl border border-amber-200 bg-white p-5 shadow-sm">
        <h3 class="mb-3 text-lg font-semibold text-gray-950">Factures a examiner avant envoi</h3>
        <p class="mb-4 text-sm text-gray-600">Le directeur consulte le detail et le PDF avant toute decision.</p>
        @forelse ($pendingInvoices as $invoice)
        <div class="mb-4 rounded-xl border border-gray-200 p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div><strong>{{ $invoice->number }}</strong><p class="text-sm text-gray-600">{{ $invoice->client?->name ?? '—' }} | TTC : {{ number_format((float)$invoice->total_ttc, 2, ',', ' ') }} DH</p></div>
                <div class="flex gap-3 text-sm"><a href="{{ route('invoices.show', $invoice) }}" class="font-semibold text-amber-800 underline">Voir la facture</a><a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" rel="noopener" class="font-semibold text-amber-800 underline">Previsualiser PDF</a></div>
            </div>
            <div class="mt-3 overflow-x-auto"><table class="w-full text-left text-xs"><thead><tr><th>Produit</th><th>Qte</th><th>Prix HT</th><th>Remise</th></tr></thead><tbody>
            @foreach ($invoice->items as $item)<tr class="border-t border-gray-100"><td class="py-1">{{ $item->name }}</td><td>{{ $item->quantity }}</td><td>{{ number_format((float)$item->unit_price, 2, ',', ' ') }} DH</td><td>{{ $item->discount_percent }}%</td></tr>@endforeach
            </tbody></table></div>
            <form action="{{ route('documents.release.decide', ['type' => 'invoice', 'id' => $invoice->id]) }}" method="POST" class="mt-3 flex flex-wrap gap-2">
                @csrf
                <input name="comment" maxlength="1000" placeholder="Motif du refus" class="rounded border-gray-300 text-sm">
                <button type="submit" name="decision" value="approved" class="rounded-lg bg-green-700 px-3 py-2 text-sm font-semibold text-white">Approuver</button>
                <button type="submit" name="decision" value="rejected" class="rounded-lg border border-red-400 px-3 py-2 text-sm font-semibold text-red-700">Refuser</button>
            </form>
        </div>
        @empty<p class="text-sm text-gray-500">Aucune facture en attente de validation d'envoi.</p>@endforelse
    </section>
'''
    mod(view,'</x-app-layout>',invoiceSection+'</x-app-layout>')

    # Hide original quote download link for non-approved commercial; admin can always preview.
    v='resources/views/quotes/show.blade.php'
    mod(v,'''<a href="{{ route('quotes.pdf', [$quote, 'doc' => 'devis']) }}" class="{{ $hBtn }}">📄 Télécharger le devis</a>''', '''@if (auth()->user()->isAdmin() || $quote->approval_status === 'approved')
                    <a href="{{ route('quotes.pdf', [$quote, 'doc' => 'devis']) }}" class="{{ $hBtn }}">{{ auth()->user()->isAdmin() ? 'Previsualiser le devis PDF' : 'Telecharger le devis approuve' }}</a>
                @else
                    <span class="text-xs font-semibold text-amber-700">PDF bloque en attente de validation</span>
                @endif''')
    # Invoice: admin can review full invoice, commercial PDF only after client release approval.
    inv='app/Http/Controllers/InvoiceController.php'
    mod(inv,"public function pdf(Request $request, Invoice $invoice)\n    {", "public function pdf(Request $request, Invoice $invoice)\n    {\n        abort_unless($request->user()->isAdmin() || app(\\App\\Services\\DocumentReleaseApproval::class)->canRelease($invoice), 403);")
    # Readonly admin invoice visibility: existing show and PDF routes, add navigation hint in invoice show.
    iv='resources/views/invoices/show.blade.php'
    mod(iv, "<a href=\"{{ route('invoices.pdf', $invoice) }}\" target=\"_blank\"", "<a href=\"{{ route('invoices.pdf', $invoice) }}\" target=\"_blank\"") if False else None
    # Existing release-review panel from Sprint C already handles admin invoice accept/reject after request.
    # Don't mutate invoice display until confirmed route and panel placement in current copy.
    for name,data in changes.items():
        compile(data,name,'exec') if False else None
    BACKUP.mkdir(exist_ok=False)
    for name,data in changes.items():
        target=ROOT/name; dest=BACKUP/name;dest.parent.mkdir(parents=True,exist_ok=True); shutil.copy2(target,dest)
    for name,data in changes.items(): (ROOT/name).write_text(data,encoding='utf-8')
    for name in changes:
        if name.endswith('.php') and not name.endswith('.blade.php'):
            t=subprocess.run(['php','-l',str(ROOT/name)],capture_output=True,text=True)
            print(t.stdout.strip());
            if t.returncode: raise RuntimeError(t.stderr or t.stdout)
    for cmd in [['php','artisan','route:clear'],['php','artisan','view:cache']]:
        t=subprocess.run(cmd,cwd=ROOT,text=True);print('exit',t.returncode,'for',cmd)
        if t.returncode:raise RuntimeError('Artisan command failed')
    print('SPRINT E INSTALLED. Backup:',BACKUP)
    print('Quotes: auto pending, admin full details and PDF review, commercial PDF/convert blocked until approved.')
    print('Invoices: Admin PDF review, commercial requires separate client release approval. No migration/no send.')
except Exception as e:
    print('INSTALL FAILED:',e,file=sys.stderr)
    if BACKUP.exists():
        for f in BACKUP.rglob('*'):
            if f.is_file():
                dest=ROOT/f.relative_to(BACKUP);dest.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(f,dest)
        print('Changes rolled back from',BACKUP,file=sys.stderr)
    sys.exit(1)
