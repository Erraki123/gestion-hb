<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderApprovalController extends Controller
{
    public function request(Request $request, Order $order)
    {
        abort_unless($request->user()->isAdmin() || $order->commercial_id === $request->user()->id, 403);
        DB::transaction(function () use ($order, $request) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($order->status !== 'nouvelle' || $order->approval_status === 'pending') {
                throw ValidationException::withMessages(['approval' => 'Demande impossible pour cette commande.']);
            }
            $order->update([
                'approval_status' => 'pending', 'approval_requested_by' => $request->user()->id,
                'approval_requested_at' => now(), 'approval_decided_by' => null,
                'approval_decided_at' => null, 'approval_comment' => null,
            ]);
        });
        return back()->with('success', 'Demande de validation envoyée à l’administration.');
    }

    public function decide(Request $request, Order $order)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);
        DB::transaction(function () use ($order, $request, $data) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($order->status !== 'nouvelle' || $order->approval_status !== 'pending') {
                throw ValidationException::withMessages(['approval' => 'Aucune demande en attente.']);
            }
            $order->update([
                'approval_status' => $data['decision'],
                'approval_decided_by' => $request->user()->id,
                'approval_decided_at' => now(),
                'approval_comment' => $data['comment'] ?? null,
            ]);
        });
        return back()->with('success', 'Décision enregistrée.');
    }
}
