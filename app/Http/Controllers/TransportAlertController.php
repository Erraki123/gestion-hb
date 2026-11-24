<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Support\TransportAlerts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;

class TransportAlertController extends Controller
{
    /** JSON pour la cloche (rafraîchie toutes les minutes). */
    public function index()
    {
        $s = TransportAlerts::summary();

        return response()->json([
            'total' => $s['total'],
            'html'  => $s['total'] > 0
                ? Blade::render('<x-transport-list :items="$items" />', ['items' => $s['items']])
                : '',
        ]);
    }

    /** Arrêter, reporter ou réactiver l'alarme : toujours une action volontaire d'une personne. */
    public function update(Request $request, Invoice $invoice)
    {
        abort_unless($invoice->type === 'facture' && $invoice->transport_remind_at, 404);

        $data = $request->validate([
            'action'    => ['required', 'in:stop,snooze,reactivate'],
            'remind_at' => ['required_if:action,snooze', 'nullable', 'date'],
        ]);

        match ($data['action']) {
            'stop' => $invoice->update([
                'transport_alarm_stopped_at' => now(),
                'transport_alarm_stopped_by' => auth()->id(),
            ]),
            'snooze' => $invoice->update([
                'transport_remind_at'        => $data['remind_at'],
                'transport_alarm_stopped_at' => null,
                'transport_alarm_stopped_by' => null,
            ]),
            'reactivate' => $invoice->update([
                'transport_alarm_stopped_at' => null,
                'transport_alarm_stopped_by' => null,
            ]),
        };

        return back()->with('success', match ($data['action']) {
            'stop'       => 'Alarme de transport arrêtée.',
            'snooze'     => 'Alarme reportée au ' . \Illuminate\Support\Carbon::parse($data['remind_at'])->format('d/m/Y') . '.',
            'reactivate' => 'Alarme de transport réactivée.',
        });
    }
}
