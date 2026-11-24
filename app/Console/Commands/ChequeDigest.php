<?php

namespace App\Console\Commands;

use App\Mail\ChequeDigestMail;
use App\Models\MessageLog;
use App\Models\Payment;
use App\Models\User;
use App\Services\WhatsAppException;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

/**
 * Résumé quotidien des chèques à encaisser (échéance dans 3 jours ou dépassée).
 * Admin : tous les chèques. Commercial : seulement les siens.
 */
class ChequeDigest extends Command
{
    protected $signature = 'cheques:digest {--force : renvoyer même si déjà envoyé aujourd\'hui}';
    protected $description = 'Envoie par e-mail / WhatsApp les chèques à encaisser';

    public function handle(WhatsAppService $wa): int
    {
        if (! config('notify.digest.enabled')) {
            return self::SUCCESS;
        }

        $days = (int) config('notify.digest.days', 3);

        $cheques = Payment::withoutGlobalScopes()->with(['client', 'invoice'])
            ->where('status', 'valide')->where('method', 'cheque')->where('cheque_status', 'en_attente')
            ->whereNotNull('cheque_due_date')
            ->whereDate('cheque_due_date', '<=', today()->addDays($days)->toDateString())
            ->orderBy('cheque_due_date')->get();

        if ($cheques->isEmpty()) {
            $this->info('Aucun chèque à signaler.');

            return self::SUCCESS;
        }

        foreach (User::where('role', 'admin')->where('is_active', true)->get() as $admin) {
            $this->notify($admin, $cheques, $wa);
        }

        foreach ($cheques->groupBy('commercial_id') as $cid => $list) {
            $u = User::where('role', 'commercial')->where('is_active', true)->find($cid);

            if ($u) {
                $this->notify($u, $list, $wa);
            }
        }

        return self::SUCCESS;
    }

    private function notify(User $user, Collection $list, WhatsAppService $wa): void
    {
        $groups = $this->groups($list);
        $count  = $list->count();
        $sum    = (float) $list->sum('amount');

        if (config('notify.email.enabled') && $user->email) {
            $this->once($user, 'email', $user->email, '🔔 ' . $count . ' chèque(s) à encaisser', function () use ($user, $groups, $count, $sum) {
                Mail::to($user->email)->send(new ChequeDigestMail($user->name, $groups, $count, $sum));
            });
        }

        if ($wa->enabled() && $user->phone) {
            $text = $this->whatsappText($user, $groups, $count, $sum);

            $this->once($user, 'whatsapp', $user->phone, '🔔 ' . $count . ' chèque(s) à encaisser', function () use ($wa, $user, $text) {
                $wa->sendText($user->phone, $text, 'cheque-digest-' . $user->id . '-' . today()->format('Ymd'));
            });
        }
    }

    private function once(User $user, string $channel, string $to, string $subject, \Closure $send): void
    {
        $exists = MessageLog::where('kind', 'cheque_digest')->where('channel', $channel)
            ->where('recipient', $to)->where('status', 'sent')->whereDate('created_at', today())->exists();

        if ($exists && ! $this->option('force')) {
            return;
        }

        $log = MessageLog::create([
            'channel' => $channel, 'kind' => 'cheque_digest', 'recipient' => $to,
            'subject' => $subject, 'status' => 'queued',
        ]);

        try {
            $send();
            $log->update(['status' => 'sent', 'sent_at' => now()]);
            $this->line("✓ {$channel} → {$user->name}");
        } catch (WhatsAppException|\Throwable $e) {
            $log->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500)]);
            $this->error("✗ {$channel} → {$user->name} : " . $e->getMessage());
        }
    }

    /** @return array<string, array{title: string, rows: array}> */
    private function groups(Collection $list): array
    {
        $g = ['late' => ['title' => '🔴 En retard', 'rows' => []],
              'today' => ['title' => '🟠 Aujourd\'hui', 'rows' => []],
              'soon' => ['title' => '🟡 Dans les 3 jours', 'rows' => []]];

        foreach ($list as $p) {
            $d = (int) today()->diffInDays($p->cheque_due_date, false);
            $key = $d < 0 ? 'late' : ($d === 0 ? 'today' : 'soon');

            $g[$key]['rows'][] = [
                'client'  => $p->client?->name ?? '—',
                'invoice' => $p->invoice?->displayNumber() ?? '—',
                'number'  => $p->cheque_number,
                'amount'  => (float) $p->amount,
                'due'     => $p->cheque_due_date->format('d/m/Y'),
                'when'    => $d < 0 ? abs($d) . ' j de retard' : ($d === 0 ? 'aujourd\'hui' : ($d === 1 ? 'demain' : 'dans ' . $d . ' j')),
            ];
        }

        return array_filter($g, fn ($x) => $x['rows'] !== []);
    }

    private function whatsappText(User $user, array $groups, int $count, float $sum): string
    {
        $out = ["🔔 *Chèques à encaisser* — " . now()->format('d/m/Y'), "Bonjour {$user->name},", ''];

        foreach ($groups as $g) {
            $out[] = '*' . $g['title'] . '*';

            foreach ($g['rows'] as $r) {
                $out[] = "• {$r['client']} — " . number_format($r['amount'], 2, ',', ' ') . " DH — {$r['invoice']} (chèque {$r['number']}, éch. {$r['due']}, {$r['when']})";
            }

            $out[] = '';
        }

        $out[] = "Total : {$count} chèque(s) · " . number_format($sum, 2, ',', ' ') . ' DH';

        return implode("\n", $out);
    }
}
