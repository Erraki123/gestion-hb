<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Quote;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** Separate, content-bound approval for client release; does not change price/order approvals. */
class DocumentReleaseApproval
{
    public function type(Model $document): string
    {
        return match (true) {
            $document instanceof Quote => 'quote',
            $document instanceof Invoice => 'invoice',
            default => throw new \InvalidArgumentException('Unsupported document'),
        };
    }

    public function eligible(Model $document): bool
    {
        if ($document instanceof Quote) {
            return (bool) $document->number
                && in_array($document->status, ['brouillon', 'envoye', 'accepte'], true)
                && $document->canSendToClient();
        }
        return $document instanceof Invoice
            && $document->type === 'facture'
            && (bool) $document->number
            && in_array($document->status, ['emise', 'partiellement_payee', 'cheque_en_attente', 'payee'], true);
    }

    /** Include every document line and the recipient address in the approval digest. */
    public function fingerprint(Model $document): string
    {
        $document->load(['items', 'client']);
        $excluded = ['created_at', 'updated_at', 'deleted_at', 'amount_paid', 'amount_cashed',
            'amount_cheque_pending', 'stock_moved', 'transport_alarm_stopped_at',
            'transport_alarm_stopped_by'];
        $attributes = array_diff_key($document->getAttributes(), array_flip($excluded));
        ksort($attributes);
        $items = $document->items->map(function ($line) {
            $attributes = array_diff_key($line->getAttributes(), array_flip(['created_at', 'updated_at']));
            ksort($attributes);
            return $attributes;
        })->sortBy('id')->values()->all();
        $recipient = [
            'client_id' => $document->client_id,
            'email' => (string) ($document->client?->email ?? ''),
            'phone' => (string) ($document->client?->phone ?? ''),
        ];
        return hash('sha256', json_encode([
            'type' => $this->type($document), 'attributes' => $attributes,
            'items' => $items, 'recipient' => $recipient,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    public function latest(Model $document): ?object
    {
        return DB::table('document_release_approvals')
            ->where('document_type', $this->type($document))
            ->where('document_id', $document->getKey())
            ->orderByDesc('id')->first();
    }

    public function status(Model $document): string
    {
        $entry = $this->latest($document);
        if (! $entry) return 'not_requested';
        if (! hash_equals((string) $entry->fingerprint, $this->fingerprint($document))) return 'changed';
        return (string) $entry->status;
    }

    public function canRelease(Model $document): bool
    {
        return $this->eligible($document) && $this->status($document) === 'approved';
    }

    /** Admin explicitly authorises the current version while sending it.
     * The approval remains fingerprint-bound for asynchronous delivery checks.
     * This method must only be invoked by a server-side admin-authorised action.
     */
    public function approveForAdminSend(Model $document, int $adminId): void
    {
        DB::transaction(function () use ($document, $adminId) {
            // Prevent two concurrent send approvals from authorising different versions.
            $current = $document->newQuery()->whereKey($document->getKey())
                ->lockForUpdate()->firstOrFail();

            if ($current instanceof Quote && (bool) $current->number
                && in_array($current->status, ['brouillon', 'envoye', 'accepte'], true)
                && ! $current->canSendToClient()) {
                // This is an explicit director decision, not a commercial bypass.
                $current->update([
                    'approval_status' => 'approved',
                    'approval_decided_by' => $adminId,
                    'approval_decided_at' => now(),
                    'approval_comment' => 'Validation directe par la direction lors de l envoi',
                ]);
            }

            if (! $this->eligible($current)) {
                throw new \DomainException('Document non eligible pour envoi.');
            }

            // Existing valid approval can be reused; preserve audit history.
            if ($this->canRelease($current)) return;

            $now = now();
            DB::table('document_release_approvals')->insert([
                'document_type' => $this->type($current),
                'document_id' => $current->getKey(),
                'fingerprint' => $this->fingerprint($current),
                'status' => 'approved',
                'requested_by' => $adminId,
                'requested_at' => $now,
                'decided_by' => $adminId,
                'decided_at' => $now,
                'comment' => 'Autorisation directe lors de l envoi par la direction',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });

        $document->refresh();
    }

    /** Whether the admin's direct-send action can be displayed (before approval). */
    public function eligibleForAdminSend(Model $document): bool
    {
        if ($document instanceof Quote) {
            return (bool) $document->number
                && in_array($document->status, ['brouillon', 'envoye', 'accepte'], true);
        }

        return $this->eligible($document);
    }

    public function request(Model $document, int $userId): void
    {
        if (! $this->eligible($document)) throw new \DomainException('Document not eligible for release.');
        if ($this->status($document) === 'pending') return;
        DB::table('document_release_approvals')->insert([
            'document_type' => $this->type($document), 'document_id' => $document->getKey(),
            'fingerprint' => $this->fingerprint($document), 'status' => 'pending',
            'requested_by' => $userId, 'requested_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function decide(Model $document, int $adminId, string $decision, ?string $comment = null): void
    {
        if (! in_array($decision, ['approved', 'rejected'], true)) throw new \InvalidArgumentException('Invalid decision');
        DB::transaction(function () use ($document, $adminId, $decision, $comment) {
            $entry = DB::table('document_release_approvals')
                ->where('document_type', $this->type($document))
                ->where('document_id', $document->getKey())
                ->orderByDesc('id')->lockForUpdate()->first();
            if (! $entry || $entry->status !== 'pending'
                || ! hash_equals((string) $entry->fingerprint, $this->fingerprint($document))
                || ! $this->eligible($document)) {
                throw new \DomainException('Request missing, changed, or no longer eligible.');
            }
            DB::table('document_release_approvals')->where('id', $entry->id)->update([
                'status' => $decision, 'decided_by' => $adminId, 'decided_at' => now(),
                'comment' => $comment, 'updated_at' => now(),
            ]);
        });
    }
}
