#!/usr/bin/env python3
"""Patch only the five approved CRM document display/download paths.
No writes to the database, no external communication, no queue changes.
"""
import argparse, pathlib, shutil, subprocess, sys, datetime, re

ROOT = pathlib.Path.cwd()
CONTROLLER = pathlib.Path('app/Http/Controllers/Admin/HnbChromePdfController.php')
ROUTES = pathlib.Path('routes/hnb_chrome_pdf.php')
DELIVERY = pathlib.Path('app/Http/Controllers/DeliveryController.php')
PAYMENT = pathlib.Path('app/Http/Controllers/PaymentController.php')
QUOTE = pathlib.Path('app/Http/Controllers/QuoteController.php')
FILES = [CONTROLLER, ROUTES, DELIVERY, PAYMENT, QUOTE]


def expect(old, needle, label):
    if needle not in old: raise RuntimeError(f"Cannot safely patch {label}: anchor not found. No changes made.")


def change_controller(s):
    if 'HNB_ALL_TYPES_V34' in s:
        return s
    expect(s, 'use App\\Models\\Payment;', 'controller imports')
    expect(s, "['quote', 'invoice', 'delivery', 'receipt']", 'type whitelist')
    expect(s, "'receipt' => Payment::findOrFail($id),", 'ownership switch')
    expect(s, "'receipt' => $document->status === 'valide'", 'permission switch')
    expect(s, "if ($type === 'quote') {", 'build quote')
    expect(s, "} elseif ($type === 'invoice') {", 'build invoice')
    s=s.replace('use App\\Models\\Payment;', 'use App\\Models\\Payment;\nuse App\\Models\\Order; // HNB_ALL_TYPES_V34')
    s=s.replace("['quote', 'invoice', 'delivery', 'receipt']", "['quote', 'order', 'invoice', 'delivery', 'receipt']", 1)
    s=s.replace("'quote' => Quote::findOrFail($id),", "'quote' => Quote::findOrFail($id),\n            'order' => Order::findOrFail($id),", 1)
    s=s.replace("'quote' => $document->approval_status === 'approved',", "'quote' => $document->approval_status === 'approved',\n            'order' => $document->approval_status === 'approved'\n                && ! in_array($document->status, ['annulee', 'annule'], true),", 1)
    # Invoice PDF availability after director approval to issue; sending still has a separate release gate.
    old_invoice = "'invoice' => $document->type === 'facture'\n                && ! $document->isDraft()\n                && $release->canRelease($document),"
    new_invoice = "'invoice' => $document->type === 'facture'\n                && ! $document->isDraft()\n                && (app(\\App\\Services\\InvoiceIssueApproval::class)->status($document) === 'approved'\n                    || $release->canRelease($document)),"
    expect(s, old_invoice, 'invoice PDF approval')
    s = s.replace(old_invoice, new_invoice, 1)

    # Quote always remains quote, even if the underlying quote has later been converted into an order.
    expect(s, "$d['title'] = $q->order ? 'BON DE COMMANDE' : 'DEVIS';", 'legacy quote label')
    expect(s, "$d['number'] = $q->order ? $q->order->number : $q->number;", 'legacy quote number')
    expect(s, "$d['subtitle'] = $q->order ? 'Devis d’origine : ' . $q->number :\n                'Valable jusqu’au : ' . ($q->valid_until?->format('d/m/Y') ?? '—');", 'legacy quote subtitle')
    s=s.replace("$d['title'] = $q->order ? 'BON DE COMMANDE' : 'DEVIS';", "$d['title'] = 'DEVIS';", 1)
    s=s.replace("$d['number'] = $q->order ? $q->order->number : $q->number;", "$d['number'] = $q->number;", 1)
    s=s.replace("$d['subtitle'] = $q->order ? 'Devis d’origine : ' . $q->number :\n                'Valable jusqu’au : ' . ($q->valid_until?->format('d/m/Y') ?? '—');", "$d['subtitle'] = 'Valable jusqu’au : ' . ($q->valid_until?->format('d/m/Y') ?? '—');", 1)
    s=s.replace("if (! $q->order) $d['signatures'][] = 'Bon pour accord (nom, date, signature)';", "if (! $q->order) $d['signatures'][] = 'Bon pour accord (nom, date, signature)';", 1) # preserve
    order='''} elseif ($type === 'order') {
            // HNB_ALL_TYPES_V34: order data comes from Orders, not a quote transformed into a faux order.
            $o = Order::with(['client', 'items', 'quote'])->findOrFail($id);
            $d['title'] = 'BON DE COMMANDE';
            $d['number'] = $o->number ?: ('Commande-' . $o->id);
            $d['date'] = ($o->created_at?->format('d/m/Y')) ?? '';
            $d['client'] = $this->client($o->client);
            $d['subtitle'] = $o->quote ? ('Devis d’origine : ' . $o->quote->number) : '';
            if (in_array($o->status, ['annulee', 'annule'], true)) $d['stamp'] = 'ANNULÉE';
            foreach ($o->items as $line) $d['items'][] = $this->item($line);
            $d['taxes'] = DocumentTotals::breakdown($o->items);
            $d['totals'][] = ['Total HT', $this->money($o->subtotal_ht)];
            foreach ($d['taxes'] as $tax) {
                $d['totals'][] = ['TVA ' . ($tax['rate'] + 0) . ' %', $this->money($tax['tax'])];
            }
            $d['totals'][] = ['TOTAL TTC', $this->money($o->total_ttc), 'grand'];
        '''
    s=s.replace("} elseif ($type === 'invoice') {", order+"} elseif ($type === 'invoice') {", 1)
    return s


def change_routes(s):
    if 'HNB_ALL_TYPES_V34' in s: return s
    expect(s, "'quote|invoice|delivery|receipt'", 'studio type constraint')
    s=s.replace("'quote|invoice|delivery|receipt'", "'quote|order|invoice|delivery|receipt'",1)
    s+='''\n// HNB_ALL_TYPES_V34: client bon de commande, same auth + director approval inside Studio.\nRoute::middleware(['web', 'auth', 'active', 'role:admin,commercial'])\n    ->get('orders/{order}/pdf', function (\\App\\Models\\Order $order) {\n        return redirect()->route('admin.pdf-studio.show', ['type' => 'order', 'id' => $order->id, 'download' => 1]);\n    })->name('orders.pdf');\n'''
    return s


def safe_redirect(s, fname, marker):
    if 'HNB_ALL_TYPES_V34' in s: return s
    # Preserve all existing checks before the DomPDF invocation.
    pat=r'(public function pdf\([^)]*\\?'+fname+r' \\$[A-Za-z]+\))'
    # Simpler: extract the full pdf method containing expected name.
    marker_func = f'public function pdf({fname} $'
    pos=s.find(marker_func)
    if pos<0: raise RuntimeError(f"Cannot find {fname} PDF method; no changes")
    start=s.find('{',pos)
    depth=1; j=start+1
    while depth and j<len(s):
        if s[j]=='{':depth+=1
        elif s[j]=='}':depth-=1
        j+=1
    part=s[pos:j]
    expect(part, 'Pdf::loadView(', fname+' DomPDF method')
    # use original authorization and loading statements, just replace the renderer call at its first return
    ix=part.find('return Pdf::loadView(')
    if ix<0:raise RuntimeError(f"Unexpected PDF flow in {fname}; no changes")
    replacement="return redirect()->route('admin.pdf-studio.show', ['type' => '" + ('delivery' if fname=='Delivery' else 'receipt') + "', 'id' => $"+('delivery' if fname=='Delivery' else 'payment')+"->id, 'download' => 1]); // HNB_ALL_TYPES_V34"
    part=part[:ix]+replacement+'\n    }'
    return s[:pos]+part+s[j:]


def change_quote(s):
    if 'HNB_ALL_TYPES_V34' in s: return s
    pos=s.index('public function pdf('); nxt=s.index('public function ',pos+20)
    part=s[pos:nxt]
    expect(part, "'type' => 'quote', 'id' => $quote->id, 'download' => 1,", 'quote bridge')
    token="        // HNB_CHROME_LEGACY_BRIDGE: keep the existing route; centralize PDF authorization in Studio."
    expect(part,token,'quote pdf bridge marker')
    add='''        // HNB_ALL_TYPES_V34: explicitly requested Bon de commande, based on the actual linked order.
        if (in_array($request->query('doc'), ['commande', 'bon-de-commande', 'order'], true)) {
            abort_unless($quote->order, 404, 'Aucune commande liée à ce devis.');
            return redirect()->route('admin.pdf-studio.show', [
                'type' => 'order', 'id' => $quote->order->id, 'download' => 1,
            ]);
        }
'''
    part=part.replace(token,add+token,1)
    return s[:pos]+part+s[nxt:]


def run():
    if not (ROOT/'artisan').exists():
        raise RuntimeError('Run from Laravel project root (artisan missing).')
    original={}
    for f in FILES:
        if not f.is_file():raise RuntimeError(f'Missing {f}; no changes made.')
        original[f]=f.read_text(encoding='utf-8-sig')
    output={CONTROLLER:change_controller(original[CONTROLLER]),ROUTES:change_routes(original[ROUTES]),
            DELIVERY:safe_redirect(original[DELIVERY],'Delivery','delivery'),
            PAYMENT:safe_redirect(original[PAYMENT],'Payment','receipt'),
            QUOTE:change_quote(original[QUOTE])}
    changed=[f for f in FILES if output[f]!=original[f]]
    if not changed:
        print('Already installed, no modifications.');return
    backup=ROOT/('backup-hnb-chromium-alltypes-'+datetime.datetime.now().strftime('%Y%m%d-%H%M%S'))
    for f in changed:
        destination=backup/f
        destination.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(f,destination)
    print('Backup:',backup)
    try:
        for f in changed:
            f.write_text(output[f],encoding='utf-8',newline='\n')
        for f in changed:
            test=subprocess.run(['php','-l',str(f)],capture_output=True,text=True,timeout=30)
            print(str(f),test.stdout.strip() or test.stderr.strip())
            if test.returncode: raise RuntimeError('PHP syntax error in '+str(f))
        for cmd in [['php','artisan','route:clear'],['php','artisan','view:clear'],['php','artisan','view:cache'],['php','artisan','route:list','--name=orders.pdf']]:
            res=subprocess.run(cmd,capture_output=True,text=True,timeout=70)
            print(' '.join(cmd),res.returncode, (res.stdout+res.stderr).strip()[-1100:])
            if res.returncode: raise RuntimeError('Failed: '+' '.join(cmd))
        print('SUCCESS: Chromium Premium active for Devis, Facture, Bon de commande, Livraison, Recu routes.')
        print('Approval checks in Studio remain active. No email, WhatsApp, database, jobs or senders altered.')
    except Exception:
        for f in changed: shutil.copy2(backup/f,f)
        subprocess.run(['php','artisan','optimize:clear'],capture_output=True)
        print('FAILED: all changed files restored from', backup, file=sys.stderr)
        raise

if __name__=='__main__':
 try:run()
 except Exception as e: print('ERROR:',e,file=sys.stderr); sys.exit(1)
