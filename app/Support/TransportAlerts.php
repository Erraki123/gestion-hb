<?php

namespace App\Support;

use App\Models\Invoice;

/**
 * Alarmes de transport actives : date de rappel posée, pas encore arrêtée par une personne.
 * Le Global Scope de Invoice limite automatiquement un commercial à ses propres factures.
 */
class TransportAlerts
{
    /** Toutes les alarmes actives (échues d'abord). */
    public static function active(): array
    {
        $today = today();

        return Invoice::with('client')
            ->where('type', 'facture')
            ->whereNotIn('status', ['brouillon', 'annulee'])
            ->where('transport_amount', '>', 0)
            ->whereNotNull('transport_remind_at')
            ->whereNull('transport_alarm_stopped_at')
            ->orderBy('transport_remind_at')
            ->limit(100)
            ->get()
            ->map(fn (Invoice $i) => [
                'invoice_id' => $i->id,
                'invoice'    => $i->displayNumber(),
                'client'     => $i->client?->name ?? '—',
                'carrier'    => $i->transport_carrier,
                'phone'      => $i->transport_phone,
                'tel'        => $i->transport_phone ? preg_replace('/[^0-9+]/', '', $i->transport_phone) : null,
                'amount'     => (float) $i->transport_amount,
                'note'       => $i->transport_note,
                'remind_at'  => $i->transport_remind_at,
                'days'       => (int) $today->diffInDays($i->transport_remind_at, false), // <= 0 : à appeler maintenant
                'balance'    => (float) $i->balance(),
            ])
            ->all();
    }

    /** Seulement celles dont le jour est arrivé (ou dépassé). */
    public static function due(): array
    {
        return array_values(array_filter(self::active(), fn ($a) => $a['days'] <= 0));
    }

    /** Données pour la cloche. */
    public static function summary(): array
    {
        $items = self::due();

        return [
            'total' => count($items),
            'items' => $items,
        ];
    }
}
