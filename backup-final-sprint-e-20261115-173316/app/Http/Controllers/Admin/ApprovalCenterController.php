<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Quote;
use Illuminate\Http\Request;

class ApprovalCenterController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        // Les conditions de validation sont contrôlées de nouveau dans
        // QuoteApprovalController et OrderApprovalController (transaction + verrou).
        $quotes = Quote::query()
            ->with(['client', 'commercial'])
            ->where('requires_approval', true)
            ->where('approval_status', 'pending')
            ->whereIn('status', ['brouillon', 'envoye', 'accepte'])
            ->whereDoesntHave('order')
            ->orderByDesc('approval_requested_at')
            ->paginate(10, ['*'], 'quotes_page');

        $orders = Order::query()
            ->with(['client', 'commercial'])
            ->where('approval_status', 'pending')
            ->where('status', 'nouvelle')
            ->orderByDesc('approval_requested_at')
            ->paginate(10, ['*'], 'orders_page');

        $counts = [
            'quotes' => Quote::query()->where('requires_approval', true)->where('approval_status', 'pending')
                ->whereIn('status', ['brouillon', 'envoye', 'accepte'])->whereDoesntHave('order')->count(),
            'orders' => Order::query()->where('approval_status', 'pending')->where('status', 'nouvelle')->count(),
            'quotes_approved' => Quote::query()->where('approval_status', 'approved')->count(),
            'orders_approved' => Order::query()->where('approval_status', 'approved')->count(),
        ];

        return view('admin.approvals.index', compact('quotes', 'orders', 'counts'));
    }
}
