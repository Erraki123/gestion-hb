# -*- coding: utf-8 -*-
"""Safe restore: Admin original quote/order workflow; retain Commercial approvals."""
from pathlib import Path
from datetime import datetime
import shutil, subprocess, sys

root = Path.cwd()
paths = {
    'quote': root / 'app/Http/Controllers/QuoteController.php',
    'order_view': root / 'resources/views/orders/show.blade.php',
    'invoice_view': root / 'resources/views/invoices/show.blade.php',
    'quote_view': root / 'resources/views/quotes/show.blade.php',
}
missing = [str(p) for p in paths.values() if not p.is_file()]
if missing:
    sys.exit('STOP: missing source files; nothing changed: ' + ', '.join(missing))
original = {key: p.read_text(encoding='utf-8-sig') for key, p in paths.items()}
changed = dict(original)
marker = 'HNB_ADMIN_RESTORE_ORIGINAL_FLOW_V1'
if marker in original['quote']:
    print('Already applied. No changes.')
    sys.exit(0)

def replace_once(key, old, new):
    count = changed[key].count(old)
    if count != 1:
        raise RuntimeError(f'{key}: expected exactly one match; found {count}: {old[:85]!r}')
    changed[key] = changed[key].replace(old, new, 1)

try:
    replace_once('quote',
        "'requires_approval' => true,\n                'approval_status' => 'pending',\n                'approval_requested_by' => auth()->id(),\n                'approval_requested_at' => now(),",
        "'requires_approval' => ! auth()->user()->isAdmin(),\n                'approval_status' => auth()->user()->isAdmin() ? null : 'pending',\n                'approval_requested_by' => auth()->user()->isAdmin() ? null : auth()->id(),\n                'approval_requested_at' => auth()->user()->isAdmin() ? null : now(),")
    replace_once('quote',
        "'requires_approval' => $sensitive, 'approval_status' => $sensitive ? 'pending' : null,\n                'approval_requested_by' => auth()->id(), 'approval_requested_at' => now(),",
        "'requires_approval' => ! auth()->user()->isAdmin(), 'approval_status' => auth()->user()->isAdmin() ? null : 'pending',\n                'approval_requested_by' => auth()->user()->isAdmin() ? null : auth()->id(), 'approval_requested_at' => auth()->user()->isAdmin() ? null : now(),")
    replace_once('quote',
        "'status'        => 'nouvelle', // confirmation réservée à l'administration",
        "'status'        => auth()->user()->isAdmin() ? 'confirmee' : 'nouvelle', // HNB_ADMIN_RESTORE_ORIGINAL_FLOW_V1")
    start = changed['quote'].index('    public function convert(')
    stop = changed['quote'].index('    private function validated(', start)
    block = changed['quote'][start:stop]
    anchor = "            return $order;\n        });\n\n        return redirect()->route('orders.show', $order)"
    if block.count(anchor) != 1:
        raise RuntimeError('quote conversion tail changed')
    extra = '''            // Original Admin behaviour: confirm and deduct stock at conversion.
            if (auth()->user()->isAdmin() && Setting::get('stock_deduction', 'order') === 'order') {
                $order->load('items');
                foreach ($order->items as $item) {
                    if (! $item->product_id) {
                        continue;
                    }
                    $stock->move($item->product()->firstOrFail(), 'sortie',
                        (float) $item->quantity, auth()->id(), 'Commande confirmée', $order->number);
                }
                $order->update(['stock_deducted' => true]);
            }
            return $order;
        });

        if (auth()->user()->isAdmin()) {
            $message = 'Commande ' . $order->number . ' confirmée.';
            try {
                app(DeliveryController::class)->autoDeliver($order->fresh());
                $message = 'Commande ' . $order->number . ' confirmée et livraison traitée.';
            } catch (ValidationException $e) {
                $message = 'Commande confirmée. Livraison à vérifier : '
                    . collect($e->errors())->flatten()->implode(' ');
            } catch (\\Throwable $e) {
                report($e);
                $message = 'Commande confirmée. Livraison à vérifier dans le journal.';
            }
            return redirect()->route('quotes.show', $quote)->with('success', $message);
        }

        return redirect()->route('orders.show', $order)'''
    extra = extra.replace('\\\\Throwable', '\\Throwable')
    block = block.replace(anchor, extra, 1)
    changed['quote'] = changed['quote'][:start] + block + changed['quote'][stop:]
    replace_once('order_view',
        "@if ($order->status === 'nouvelle' && (! auth()->user()->isAdmin() || $order->approval_status !== 'approved'))",
        "@if ($order->status === 'nouvelle' && ! auth()->user()->isAdmin())")
    replace_once('invoice_view',
        "    @include('documents._release_review', ['document' => $invoice, 'type' => 'invoice'])",
        "    {{-- Admin uses original invoice UI; Commercial approval is handled in validation centre. --}}\n    @if (! auth()->user()->isAdmin())\n        @include('documents._release_review', ['document' => $invoice, 'type' => 'invoice'])\n    @endif")
    replace_once('quote_view',
        "'text'   => 'La commande sera créée en attente de validation administrative. Aucun stock ne sera déduit avant confirmation.',",
        "'text'   => auth()->user()->isAdmin()\n                                        ? 'La commande sera confirmée et la livraison traitée selon le stock disponible.'\n                                        : 'La commande sera créée en attente de validation administrative.',")
except Exception as e:
    sys.exit('STOP: project version differs; no changes applied: ' + str(e))

if "'approval_status' => auth()->user()->isAdmin() ? null : 'pending'" not in changed['quote']:
    sys.exit('STOP: Commercial approval check missing.')
if "if ($q->approval_status !== 'approved')" not in changed['quote']:
    sys.exit('STOP: Commercial conversion approval guard missing.')
backup = root / ('backup-admin-restore-' + datetime.now().strftime('%Y%m%d-%H%M%S'))
backup.mkdir()
for key, path in paths.items():
    destination = backup / path.relative_to(root)
    destination.parent.mkdir(parents=True, exist_ok=True)
    shutil.copy2(path, destination)
try:
    for key, path in paths.items():
        path.write_text(changed[key], encoding='utf-8')
    for cmd in [['php', '-l', str(paths['quote'])], ['php', 'artisan', 'view:clear'], ['php', 'artisan', 'view:cache']]:
        print('CHECK:', ' '.join(cmd), flush=True)
        if subprocess.run(cmd, cwd=root).returncode != 0:
            raise RuntimeError('Validation failed: ' + ' '.join(cmd))
except Exception as e:
    for key, path in paths.items():
        shutil.copy2(backup / path.relative_to(root), path)
    subprocess.run(['php', 'artisan', 'view:clear'], cwd=root)
    sys.exit('ERROR: all modified files restored from backup: ' + str(e))
print('SUCCESS: Original Admin quote -> confirmed order flow restored.')
print('Commercial director approvals preserved. No database writes or outbound messages.')
print('Previously created orders remain unchanged.')
print('BACKUP:', backup)
