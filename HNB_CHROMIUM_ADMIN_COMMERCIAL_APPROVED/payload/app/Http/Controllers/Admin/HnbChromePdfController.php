<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Quote;
use App\Services\DocumentTotals;
use App\Services\DocumentReleaseApproval;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Visual proof / admin-only; NEVER replace existing approval-controlled delivery routes automatically.
 */
class HnbChromePdfController extends Controller
{
    public function show(Request $request, string $type, int $id)
    {
        abort_unless(in_array($type, ['quote', 'invoice', 'delivery', 'receipt'], true), 404);
        // Authorization is checked before rendering HTML or spawning Chromium.
        // Models also have owner global scopes; we verify ownership explicitly as defense in depth.
        $canDownload = $this->authorizeStudio($request, $type, $id);
        $doc = $this->build($type, $id);
        $viewData = [
            'doc' => $doc,
            'logoData' => $this->logoData(),
            'company' => config('commerce.company', []),
            'isPreview' => ! $request->boolean('download'),
            'studioUnapproved' => ! $canDownload,
        ];

        $html = view('pdf.chrome.document', $viewData)->render();
        if (! $request->boolean('download')) {
            return response($html)->header('Content-Type', 'text/html; charset=UTF-8')
                ->header('Cache-Control', 'private, no-store')
                ->header('X-Robots-Tag', 'noindex, nofollow');
        }

        $runner = base_path('tools/pdf-chrome/render.cjs');
        abort_unless(is_file($runner), 503, 'Chromium PDF renderer is not installed');
        $tmp = storage_path('app/pdf-chrome-private');
        File::ensureDirectoryExists($tmp);
        $key = bin2hex(random_bytes(16));
        $htmlPath = $tmp . DIRECTORY_SEPARATOR . $key . '.html';
        $pdfPath = $tmp . DIRECTORY_SEPARATOR . $key . '.pdf';
        try {
            File::put($htmlPath, $html);
            $node = (string) (config('services.hnb_pdf_node') ?: 'node');
            $process = new Process([$node, $runner, $htmlPath, $pdfPath], base_path());
            $process->setTimeout(90);
            $process->run();
            if (! $process->isSuccessful() || ! is_file($pdfPath) || filesize($pdfPath) < 500) {
                report(new \RuntimeException('Chrome PDF: ' . $process->getErrorOutput()));
                abort(503, 'Chromium PDF failed: check Node, Edge/Chrome and the renderer installation');
            }
            $bytes = File::get($pdfPath);
            $filename = preg_replace('/[^A-Za-z0-9_.-]/', '_', $doc['number'] ?: ($type . '-' . $id)) . '.pdf';
            return response($bytes)->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Cache-Control', 'private, no-store');
        } finally {
            File::delete([$htmlPath, $pdfPath]);
        }
    }

    /**
     * The Studio is an internal preview; final downloads by commercial require
     * existing director approvals. This endpoint never sends to a customer.
     *
     * @return bool whether a PDF download is permitted
     */
    private function authorizeStudio(Request $request, string $type, int $id): bool
    {
        $user = $request->user();
        abort_unless($user && in_array($user->role, ['admin', 'commercial'], true), 403);
        if ($user->isAdmin()) {
            return true; // Preserve the original director workflow.
        }

        $document = match ($type) {
            'quote' => Quote::findOrFail($id),
            'invoice' => Invoice::findOrFail($id),
            'delivery' => Delivery::findOrFail($id),
            'receipt' => Payment::findOrFail($id),
            default => abort(404),
        };
        abort_unless((int) $document->commercial_id === (int) $user->id, 404);

        $release = app(DocumentReleaseApproval::class);
        $allowed = match ($type) {
            'quote' => $document->approval_status === 'approved'
                && $release->canRelease($document),
            'invoice' => $document->type === 'facture'
                && ! $document->isDraft()
                && $release->canRelease($document),
            'delivery' => $document->status === 'valide'
                && $document->order !== null
                && $document->order->approval_status === 'approved'
                && $document->order->status !== 'annulee',
            'receipt' => $document->status === 'valide'
                && $document->invoice !== null
                && $release->canRelease($document->invoice),
        };
        if ($request->boolean('download')) {
            abort_unless($allowed, 403, 'Validation de la direction requise avant téléchargement du PDF.');
        }
        return $allowed;
    }

    private function build(string $type, int $id): array
    {
        $d = [
            'kind' => $type,
            'title' => '', 'number' => '', 'date' => '', 'subtitle' => '',
            'client' => [], 'issuer' => [], 'items' => [], 'taxes' => [], 'totals' => [],
            'sections' => [], 'payments' => [], 'signatures' => [], 'stamp' => '',
        ];
        $c = config('commerce.company', []);
        $d['issuer'] = array_filter([
            'name' => $c['name'] ?? 'HN BLOOM',
            'address' => $c['address'] ?? '', 'ice' => $c['ice'] ?? '',
            'phone' => $c['phone'] ?? '', 'email' => $c['email'] ?? '',
        ], fn ($v) => $v !== null && $v !== '');

        if ($type === 'quote') {
            $q = Quote::with(['items', 'client', 'order'])->findOrFail($id);
            $d['title'] = $q->order ? 'BON DE COMMANDE' : 'DEVIS';
            $d['number'] = $q->order ? $q->order->number : $q->number;
            $d['date'] = $q->issue_date?->format('d/m/Y') ?? '';
            $d['client'] = $this->client($q->client);
            $d['subtitle'] = $q->order ? 'Devis d’origine : ' . $q->number :
                'Valable jusqu’au : ' . ($q->valid_until?->format('d/m/Y') ?? '—');
            foreach ($q->items as $i) {
                $d['items'][] = $this->item($i);
            }
            $d['taxes'] = DocumentTotals::breakdown($q->items);
            $d['totals'] = [['Total HT', $this->money($q->subtotal_ht)]];
            foreach ($d['taxes'] as $tax) {
                $d['totals'][] = ['TVA ' . ($tax['rate'] + 0) . ' %', $this->money($tax['tax'])];
            }
            $d['totals'][] = ['TOTAL TTC', $this->money($q->total_ttc), 'grand'];
            if ($q->terms) $d['sections'][] = ['Conditions', $q->terms];
            if (! $q->order) $d['signatures'][] = 'Bon pour accord (nom, date, signature)';
        } elseif ($type === 'invoice') {
            $i = Invoice::with(['client', 'items', 'payments', 'parent'])->findOrFail($id);
            $d['title'] = strtoupper($i->title());
            $d['number'] = $i->displayNumber();
            $d['date'] = $i->issue_date?->format('d/m/Y') ?? '';
            $d['subtitle'] = $i->due_date ? 'Échéance : ' . $i->due_date->format('d/m/Y') : '';
            $d['client'] = $this->client($i->client);
            if ($i->isDraft()) $d['stamp'] = 'BROUILLON';
            elseif ($i->status === 'annulee') $d['stamp'] = 'ANNULÉE';
            elseif ($i->status === 'payee') $d['stamp'] = 'PAYÉE';
            elseif ($i->isOverdue()) $d['stamp'] = 'EN RETARD';
            foreach ($i->items as $line) $d['items'][] = $this->item($line);
            $d['taxes'] = DocumentTotals::breakdown($i->items);
            $d['totals'][] = ['Total HT', $this->money($i->subtotal_ht)];
            foreach ($d['taxes'] as $tax) {
                $d['totals'][] = ['TVA ' . ($tax['rate'] + 0) . ' % (base ' . $this->num($tax['base']) . ')', $this->money($tax['tax'])];
            }
            if ((float) ($i->transport_amount ?? 0) > 0) $d['totals'][] = ['Transport (sans TVA)', $this->money($i->transport_amount)];
            if ((float) ($i->installation_amount ?? 0) > 0) $d['totals'][] = ['Installation (sans TVA)', $this->money($i->installation_amount)];
            $d['totals'][] = ['TOTAL TTC', $this->money($i->total_ttc), 'grand'];
            $hasMovement = $i->type === 'facture' && ! $i->isDraft() && $i->status !== 'annulee'
                && ((float) $i->amount_paid > 0 || (float) $i->creditedAmount() > 0);
            if ($hasMovement) {
                $d['totals'][] = ['Déjà payé', $this->money($i->amount_paid)];
                $d['totals'][] = ['Reste à payer', $this->money($i->balance()), 'strong'];
            }
            $words = Money::words((float) $i->total_ttc);
            if ($words) $d['sections'][] = ['Montant en lettres', 'Arrêté à la somme de : ' . $words . '.'];
            if ($i->parent) {
                $d['sections'][] = ['Document lié', 'Avoir relatif à la facture ' . ($i->parent->number ?? '')];
            }
            if ($i->terms) $d['sections'][] = ['Conditions', $i->terms];
            if ($i->type === 'facture' && ! $i->isDraft()) {
                foreach ($i->payments as $p) {
                    $d['payments'][] = [
                        'date' => $p->paid_at?->format('d/m/Y') ?? '',
                        'number' => ($p->number ?? '') . ($p->status === 'annule' ? ' (annulé)' : ''),
                        'method' => $p->methodLabel(),
                        'amount' => $this->money($p->amount),
                    ];
                }
            }
        } elseif ($type === 'delivery') {
            $l = Delivery::with(['client', 'order', 'items'])->findOrFail($id);
            $d['title'] = 'BON DE LIVRAISON';
            $d['number'] = $l->displayNumber();
            $d['date'] = $l->delivery_date?->format('d/m/Y') ?? '';
            $d['client'] = $this->client($l->client);
            $d['subtitle'] = 'Commande : ' . ($l->order?->number ?? '—');
            if ($l->status === 'brouillon') $d['stamp'] = 'BROUILLON';
            if ($l->status === 'annule') $d['stamp'] = 'ANNULÉ';
            foreach ($l->items as $i) {
                $d['items'][] = [
                    'sku' => $i->sku ?? '', 'name' => $i->name ?? '',
                    'quantity' => $this->num($i->quantity), 'unit' => $i->unit ?? '',
                ];
            }
            $d['signatures'] = ['Livré par', 'Reçu par (nom, date, signature)'];
        } else {
            $p = Payment::with(['client', 'invoice', 'user'])->findOrFail($id);
            $d['title'] = 'REÇU DE PAIEMENT';
            $d['number'] = $p->number;
            $d['date'] = $p->paid_at?->format('d/m/Y') ?? '';
            $d['client'] = $this->client($p->client);
            $d['subtitle'] = 'Facture : ' . ($p->invoice?->number ?? '');
            $d['receipt'] = [
                ['Mode de paiement', $p->methodLabel()],
                ['Référence', $p->reference ?? ''],
                ['Encaissé par', $p->user?->name ?? ''],
                ['Montant reçu', $this->money($p->amount)],
                ['Reste à payer', $this->money($p->invoice?->balance() ?? 0)],
            ];
            if ($p->method === 'cheque') {
                $d['receipt'][] = ['Chèque n°', $p->cheque_number ?? ''];
                $d['receipt'][] = ['Banque', $p->cheque_bank ?? ''];
                if ($p->cheque_due_date) $d['receipt'][] = ['Échéance chèque', $p->cheque_due_date->format('d/m/Y')];
            }
            $words = Money::words((float) $p->amount);
            if ($words) $d['sections'][] = ['Montant en lettres', $words];
            if ($p->status === 'annule') $d['stamp'] = 'PAIEMENT ANNULÉ';
            if ($p->cancel_reason) $d['sections'][] = ['Motif annulation', $p->cancel_reason];
            $d['signatures'] = ['Le client', 'Le commercial'];
        }
        return $d;
    }

    private function item($i): array
    {
        return [
            'sku' => $i->sku ?? '', 'name' => $i->name ?? '',
            'quantity' => $this->num($i->quantity), 'unit' => $i->unit ?? '',
            'unit_price' => $this->num($i->unit_price),
            'tax_rate' => (string) (($i->tax_rate ?? 0) + 0),
            'discount' => (string) (($i->discount_percent ?? 0) + 0),
            'total' => $this->num($i->line_total_ht),
        ];
    }

    private function client($client): array
    {
        if (! $client) return [];
        return array_filter([
            'name' => $client->name ?? '', 'company' => $client->company ?? '',
            'address' => trim(($client->address ?? '') . ' ' . ($client->city ?? '')),
            'ice' => $client->ice ?? '',
        ], fn ($v) => $v !== null && $v !== '');
    }

    private function num($v): string { return number_format((float) $v, 2, ',', ' '); }
    private function money($v): string { return $this->num($v) . ' DH'; }

    private function logoData(): string
    {
        foreach (['images/hnbloom-premium-gold.png', 'images/hn-bloom-logo.png', 'images/logo.png'] as $relative) {
            $path = public_path($relative);
            if (is_file($path)) {
                $mime = strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'jpg' ? 'image/jpeg' : 'image/png';
                return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
            }
        }
        return '';
    }
}
