<?php

namespace App\Services;

use App\Models\DiscoveredProspect;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProspectDetail;
use App\Models\Quote;
use App\Models\WhatsAppInboundMessage;
use Illuminate\Support\Facades\DB;

/**
 * Alertes d'action calculées à partir des dossiers existants, sans mutation.
 * Une alerte n'est jamais une autorisation d'approuver ou d'envoyer.
 */
class HnbDirectionNotificationCenter
{
    public function active(): array
    {
        $alerts = [];
        $add = function (string $type, int $id, string $fingerprint, string $priority,
            string $title, string $detail, string $url, ?string $at = null) use (&$alerts): void {
            $key = $type.':'.$id;
            $alerts[$key] = [
                'key' => $key,
                'fingerprint' => hash('sha256', $type.'|'.$id.'|'.$fingerprint),
                'type' => $type, 'priority' => $priority,
                'title' => $title, 'detail' => $detail,
                'url' => $url, 'at' => $at,
            ];
        };

        foreach (Quote::query()->where('requires_approval', true)
            ->where('approval_status', 'pending')->whereIn('status', ['brouillon', 'envoye', 'accepte'])
            ->whereDoesntHave('order')->orderByDesc('approval_requested_at')->limit(15)->get() as $q) {
            $add('quote', (int) $q->id, (string) $q->approval_requested_at.'|'.$q->updated_at,
                'critical', 'Devis à valider', 'Devis '.($q->number ?: '#'.$q->id),
                route('admin.approvals.index'), (string) ($q->approval_requested_at ?: $q->created_at));
        }

        foreach (Order::query()->where('approval_status', 'pending')->where('status', 'nouvelle')
            ->orderByDesc('approval_requested_at')->limit(15)->get() as $o) {
            $add('order', (int) $o->id, (string) $o->approval_requested_at.'|'.$o->updated_at,
                'critical', 'Commande à confirmer', 'Commande '.($o->number ?: '#'.$o->id),
                route('admin.approvals.index'), (string) ($o->approval_requested_at ?: $o->created_at));
        }

        // Approbation de l'émission : seul le dernier état courant des factures brouillon compte.
        foreach (DB::table('invoice_issue_approvals')->where('status', 'pending')
            ->orderByDesc('requested_at')->limit(30)->get() as $approval) {
            $invoice = Invoice::query()->whereKey($approval->invoice_id)
                ->where('type', 'facture')->where('status', 'brouillon')->first();
            if (! $invoice) {
                continue;
            }
            // Réutilise les règles existantes de validité de demande.
            if (app(InvoiceIssueApproval::class)->status($invoice) !== 'pending') {
                continue;
            }
            $add('invoice_issue', (int) $approval->id,
                (string) $approval->fingerprint.'|'.$approval->requested_at,
                'critical', 'Facture à émettre', 'Facture '.($invoice->number ?: '#'.$invoice->id),
                route('admin.approvals.index'), (string) $approval->requested_at);
        }

        // Demandes de diffusion devis/facture, uniquement si elles sont encore valides.
        foreach (DB::table('document_release_approvals')->where('status', 'pending')
            ->whereIn('document_type', ['quote', 'invoice'])->orderByDesc('id')->limit(40)->get() as $approval) {
            $model = $approval->document_type === 'quote' ? Quote::class : Invoice::class;
            $doc = $model::query()->find($approval->document_id);
            if (! $doc || (int) (app(DocumentReleaseApproval::class)->latest($doc)?->id ?? 0) !== (int) $approval->id
                || app(DocumentReleaseApproval::class)->status($doc) !== 'pending') {
                continue;
            }
            $kind = $approval->document_type === 'quote' ? 'Devis' : 'Facture';
            $add('release', (int) $approval->id, (string) $approval->fingerprint.'|'.$approval->requested_at,
                'critical', $kind.' à autoriser pour envoi', $kind.' '.($doc->number ?: '#'.$doc->id),
                route('admin.approvals.index'), (string) $approval->requested_at);
        }

        foreach (Invoice::query()->overdue()->orderBy('due_date')->limit(20)->get() as $invoice) {
            // Réductions / avoirs / chèques en attente inclus : pas d'alerte si aucun reste réellement dû.
            if (max(0.0, $invoice->balance() - (float) $invoice->amount_cheque_pending) <= 0.005) {
                continue;
            }
            $add('overdue', (int) $invoice->id,
                (string) $invoice->due_date.'|'.$invoice->updated_at.'|'.$invoice->balance(),
                'high', 'Facture en retard', 'Facture '.($invoice->number ?: '#'.$invoice->id)
                    .' · Solde à vérifier '.number_format(max(0.0, $invoice->balance() - (float) $invoice->amount_cheque_pending), 2, ',', ' ').' MAD',
                route('invoices.show', $invoice->id), (string) $invoice->due_date);
        }

        foreach (Product::query()->where('is_active', true)->lowStock()
            ->orderBy('stock_quantity')->limit(15)->get() as $product) {
            $add('stock', (int) $product->id,
                (string) $product->stock_quantity.'|'.$product->min_stock.'|'.$product->updated_at,
                'high', 'Stock à réapprovisionner', $product->name.' · '.(string) $product->stock_quantity.' en stock',
                route('products.show', $product->id), (string) $product->updated_at);
        }

        foreach (WhatsAppInboundMessage::query()->whereNull('read_at')
            ->orderByDesc('received_at')->limit(15)->get() as $message) {
            $add('whatsapp', (int) $message->id, (string) $message->received_at,
                'normal', 'Message WhatsApp non lu', 'Réponse client à consulter',
                route('admin.whatsapp.inbox'), (string) $message->received_at);
        }

        foreach (ProspectDetail::query()->where('do_not_contact', false)
            ->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<=', now())
            ->whereNotIn('stage', ['gagne', 'perdu'])
            ->whereHas('client', fn ($q) => $q->where('status', 'prospect'))
            ->with('client:id,name,company')->orderBy('next_follow_up_at')->limit(15)->get() as $detail) {
            $add('followup', (int) $detail->id,
                (string) $detail->next_follow_up_at.'|'.$detail->updated_at,
                'normal', 'Relance Prospect à traiter',
                (string) ($detail->client?->company ?: $detail->client?->name ?: 'Prospect'),
                route('prospects.index'), (string) $detail->next_follow_up_at);
        }

        foreach (DiscoveredProspect::query()->where('status', 'pending')
            ->orderByDesc('id')->limit(15)->get() as $prospect) {
            $add('atlas', (int) $prospect->id, (string) $prospect->updated_at,
                'normal', 'ATLAS : suggestion à examiner', $prospect->company,
                route('prospects.index'), (string) ($prospect->discovered_at ?: $prospect->created_at));
        }

        // Modules de communication optionnels : vérifier la présence de la table avant lecture.
        if (DB::getSchemaBuilder()->hasTable('hnb_email_messages')) {
            foreach (DB::table('hnb_email_messages')->where('status', 'pending')
                ->orderByDesc('id')->limit(15)->get(['id', 'subject', 'created_at']) as $item) {
                $add('email', (int) $item->id, (string) $item->created_at,
                    'critical', 'Email à approuver', 'Objet : '.mb_substr((string) $item->subject, 0, 100),
                    route('hnb.email.pending'), (string) $item->created_at);
            }
        }
        if (DB::getSchemaBuilder()->hasTable('hnb_whatsapp_outbound_messages')) {
            foreach (DB::table('hnb_whatsapp_outbound_messages')->where('status', 'pending')
                ->orderByDesc('id')->limit(15)->get(['id', 'created_at']) as $item) {
                $add('whatsapp_approval', (int) $item->id, (string) $item->created_at,
                    'critical', 'WhatsApp à approuver', 'Demande de message commercial',
                    route('whatsapp.assistant.pending'), (string) $item->created_at);
            }
        }

        $rank = ['critical' => 0, 'high' => 1, 'normal' => 2];
        uasort($alerts, static fn ($a, $b) => ($rank[$a['priority']] <=> $rank[$b['priority']])
            ?: strcmp($b['at'] ?? '', $a['at'] ?? '') ?: strcmp($a['key'], $b['key']));
        return array_slice($alerts, 0, 120, true);
    }

    public function withSeen(int $adminId): array
    {
        $alerts = $this->active();
        if (! $alerts) {
            return [];
        }
        $seen = DB::table('hnb_direction_alert_reads')->where('user_id', $adminId)
            ->whereIn('alert_key', array_keys($alerts))->pluck('fingerprint', 'alert_key');
        foreach ($alerts as &$alert) {
            $alert['seen'] = isset($seen[$alert['key']]) && hash_equals((string) $seen[$alert['key']], $alert['fingerprint']);
        }
        unset($alert);
        return $alerts;
    }
}
