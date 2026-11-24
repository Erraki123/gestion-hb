<?php

namespace App\Support;

use App\Models\Payment;

/**
 * Alarme des chèques à encaisser.
 * Calculée à chaque affichage depuis la base (aucune tâche planifiée nécessaire) :
 * J-3, J-2, J-1, aujourd'hui, puis « en retard » chaque jour tant que le chèque n'est pas traité.
 * Le scope global de Payment limite déjà le commercial à ses propres chèques.
 */
class ChequeAlerts
{
    private static array $memo = [];

    public static function upcoming(int $days = 3, int $limit = 15): array
    {
        $key = $days . '-' . $limit . '-' . (auth()->id() ?? 0);

        if (isset(self::$memo[$key])) {
            return self::$memo[$key];
        }

        $base = Payment::where('status', 'valide')
            ->where('method', 'cheque')
            ->where('cheque_status', 'en_attente')
            ->whereNotNull('cheque_due_date')
            ->whereDate('cheque_due_date', '<=', today()->addDays($days)->toDateString());

        $items = (clone $base)->with(['client', 'invoice'])
            ->orderBy('cheque_due_date')->orderBy('id')->limit($limit)->get()
            ->map(fn ($p) => [
                'id'         => $p->id,
                'client'     => $p->client?->name ?? '—',
                'invoice'    => $p->invoice?->displayNumber() ?? '—',
                'invoice_id' => $p->invoice_id,
                'number'     => $p->cheque_number,
                'bank'       => $p->cheque_bank,
                'amount'     => (float) $p->amount,
                'due'        => $p->cheque_due_date,
                'days'       => (int) today()->diffInDays($p->cheque_due_date, false),
            ])->all();

        return self::$memo[$key] = [
            'items'  => $items,
            'total'  => (clone $base)->count(),
            'sum'    => (float) (clone $base)->sum('amount'),
            'urgent' => (clone $base)->whereDate('cheque_due_date', '<=', today()->toDateString())->count(),
        ];
    }

    /** [texte, classes du badge] selon les jours restants */
    public static function level(int $days): array
    {
        return match (true) {
            $days < 0   => ['En retard de ' . abs($days) . ' j', 'bg-red-100 text-red-800'],
            $days === 0 => ['Aujourd\'hui', 'bg-red-100 text-red-800'],
            $days === 1 => ['Demain', 'bg-orange-100 text-orange-800'],
            default     => ['Dans ' . $days . ' jours', 'bg-yellow-100 text-yellow-800'],
        };
    }
}
