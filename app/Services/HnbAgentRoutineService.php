<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\ProspectDetail;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Phase 9: read-only business suggestions, restricted to the current account.
 * No AI-created SQL, messaging, approvals or price/business mutations.
 */
class HnbAgentRoutineService
{
    public function candidates(User $user): array
    {
        if (! $user->is_active || ! in_array($user->role, ['admin', 'commercial'], true)) {
            return [];
        }

        if ($user->isAdmin()) {
            // Reuse the director's already validated alerts instead of duplicating approval logic.
            $rows = [];
            foreach (app(HnbDirectionNotificationCenter::class)->active() as $alert) {
                $rows[$alert['key']] = [
                    'key' => $alert['key'],
                    'fingerprint' => $alert['fingerprint'],
                    'priority' => $alert['priority'],
                    'title' => $alert['title'],
                    'detail' => $alert['detail'],
                    'url' => $alert['url'],
                    'at' => $alert['at'],
                ];
            }
            return array_slice($rows, 0, 50, true);
        }

        $rows = [];
        $add = static function (string $key, string $signature, string $priority, string $title,
            string $detail, string $url, ?string $at) use (&$rows): void {
            $rows[$key] = [
                'key' => $key,
                'fingerprint' => hash('sha256', $key.'|'.$signature),
                'priority' => $priority, 'title' => $title, 'detail' => $detail,
                'url' => $url, 'at' => $at,
            ];
        };

        // Explicit owner predicate is required: the daily CLI runs without an authenticated user.
        foreach (ProspectDetail::query()->where('do_not_contact', false)
            ->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<=', now())
            ->whereNotIn('stage', ['gagne', 'perdu'])
            ->whereHas('client', fn ($q) => $q->where('status', 'prospect')
                ->where('commercial_id', $user->id))
            ->with('client:id,name,company')
            ->orderBy('next_follow_up_at')->limit(20)->get() as $detail) {
            $client = $detail->client;
            if (! $client) continue;
            $key = 'followup:'.$detail->id;
            $due = (string) $detail->next_follow_up_at;
            $add($key, $due.'|'.$detail->stage.'|'.$detail->updated_at,
                'normal', 'Relance Prospect à traiter',
                (string) ($client->company ?: $client->name),
                route('prospects.index', ['late' => 1]), $due);
        }

        foreach (Invoice::query()->where('commercial_id', $user->id)->overdue()
            ->orderBy('due_date')->limit(20)->get() as $invoice) {
            $remaining = max(0.0, $invoice->balance() - (float) $invoice->amount_cheque_pending);
            if ($remaining <= 0.005) continue;
            $key = 'overdue:'.$invoice->id;
            $add($key, (string) $invoice->due_date.'|'.number_format($remaining, 2, '.', '')
                .'|'.$invoice->updated_at, 'high', 'Facture en retard',
                ($invoice->number ?: '#'.$invoice->id).' · Solde indicatif : '
                    .number_format($remaining, 2, ',', ' ').' MAD',
                route('invoices.show', $invoice->id), (string) $invoice->due_date);
        }

        foreach (Quote::query()->where('commercial_id', $user->id)
            ->where('status', 'envoye')->where('approval_status', 'approved')
            ->whereDoesntHave('order')->whereDate('valid_until', '>=', today())
            ->whereDate('valid_until', '<=', today()->addDays(3))
            ->orderBy('valid_until')->limit(15)->get() as $quote) {
            $key = 'quote_expiring:'.$quote->id;
            $add($key, (string) $quote->valid_until.'|'.$quote->status.'|'.$quote->updated_at,
                'normal', 'Devis bientôt expiré : suivi à vérifier',
                (string) ($quote->number ?: '#'.$quote->id),
                route('quotes.show', $quote->id), (string) $quote->valid_until);
        }

        $rank = ['critical' => 0, 'high' => 1, 'normal' => 2];
        uasort($rows, static fn ($a, $b) => ($rank[$a['priority']] <=> $rank[$b['priority']])
            ?: strcmp((string) ($a['at'] ?? ''), (string) ($b['at'] ?? ''))
            ?: strcmp($a['key'], $b['key']));
        return array_slice($rows, 0, 50, true);
    }

    public function withSnoozes(User $user): array
    {
        $items = $this->candidates($user);
        if (! $items) return [];
        $states = DB::table('hnb_agent_routine_snoozes')
            ->where('user_id', $user->id)->whereIn('task_key', array_keys($items))
            ->get(['task_key', 'fingerprint', 'snoozed_until'])->keyBy('task_key');
        foreach ($items as $key => &$item) {
            $state = $states->get($key);
            $item['snoozed'] = (bool) ($state
                && hash_equals((string) $state->fingerprint, $item['fingerprint'])
                && $state->snoozed_until && now()->lt($state->snoozed_until));
            $item['snoozed_until'] = $item['snoozed'] ? $state->snoozed_until : null;
        }
        unset($item);
        return $items;
    }

    public function snooze(User $user, string $key, string $fingerprint, int $hours): bool
    {
        if (! in_array($hours, [0, 24, 72, 168], true)) return false;
        $active = $this->candidates($user);
        $item = $active[$key] ?? null;
        if (! $item || ! hash_equals($item['fingerprint'], $fingerprint)) return false;
        if ($hours === 0) {
            DB::table('hnb_agent_routine_snoozes')->where('user_id', $user->id)
                ->where('task_key', $key)->delete();
            return true;
        }
        DB::table('hnb_agent_routine_snoozes')->upsert([[
            'user_id' => $user->id, 'task_key' => $key, 'fingerprint' => $fingerprint,
            'snoozed_until' => now()->addHours($hours),
            'created_at' => now(), 'updated_at' => now(),
        ]], ['user_id', 'task_key'], ['fingerprint', 'snoozed_until', 'updated_at']);
        return true;
    }

    /** A deterministic, account-limited answer for the existing floating assistant. */
    public function answerFor(User $user): string
    {
        $items = array_values(array_filter($this->withSnoozes($user), fn ($i) => ! $i['snoozed']));
        if (! $items) return 'Aucune action non reportée dans votre agenda CRM pour le moment.';
        $lines = array_map(static fn ($i) => '• '.$i['title'].' — '.$i['detail'], array_slice($items, 0, 5));
        return count($items).' action(s) à vérifier dans votre agenda CRM (aperçu limité) :'
            ."\n".implode("\n", $lines)
            ."\nOuvrez « Mon agenda IA » pour consulter les dossiers ou reporter un rappel."
            ."\nAucune action, approbation ou communication n’a été exécutée.";
    }

    /** A scheduled read-only scan: only the per-user daily aggregate is stored. */
    public function scanDaily(User $user, bool $dryRun = false): array
    {
        $tasks = $this->withSnoozes($user);
        $active = array_filter($tasks, static fn ($t) => ! $t['snoozed']);
        $data = [
            'total' => count($active),
            'priority' => count(array_filter($active, static fn ($t) => in_array($t['priority'], ['critical', 'high'], true))),
        ];
        if (! $dryRun) {
            DB::table('hnb_agent_routine_runs')->upsert([[
                'user_id' => $user->id, 'run_date' => today()->toDateString(),
                'total_tasks' => $data['total'], 'priority_tasks' => $data['priority'],
                'scanned_at' => now(),
            ]], ['user_id', 'run_date'], ['total_tasks', 'priority_tasks', 'scanned_at']);
        }
        return $data;
    }
}
