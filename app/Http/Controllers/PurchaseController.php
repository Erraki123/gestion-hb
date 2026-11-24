<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Page unique « Achats » : commandes, factures et fournisseurs en onglets. */
class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $tab = in_array($request->query('tab'), ['commandes', 'factures', 'fournisseurs'], true)
            ? $request->query('tab') : 'commandes';

        $allowed = [
            'commandes' => ['a_recevoir', 'brouillons'],
            'factures'  => ['a_payer', 'en_retard', 'payees'],
        ][$tab] ?? [];
        $f = in_array($request->query('f'), $allowed, true) ? $request->query('f') : null;
        $s = trim((string) $request->query('q', ''));

        $due = DB::raw('total_ttc - amount_paid');
        $stats = [
            'to_receive' => PurchaseOrder::whereIn('status', ['commande', 'recue_partielle'])->count(),
            'to_pay'     => (float) SupplierInvoice::open()->sum($due),
            'late'       => (float) SupplierInvoice::open()->whereDate('due_date', '<', today())->sum($due),
        ];

        $counts = [
            'commandes'    => PurchaseOrder::count(),
            'factures'     => SupplierInvoice::count(),
            'fournisseurs' => Supplier::count(),
        ];

        $orders = $invoices = $suppliers = null;
        $dues = collect();

        if ($tab === 'commandes') {
            $orders = PurchaseOrder::with('supplier')
                ->when($s !== '', fn ($q) => $q->where(function ($w) use ($s) {
                    $w->where('number', 'like', "%{$s}%")
                      ->orWhereHas('supplier', fn ($c) => $c->where('name', 'like', "%{$s}%"));
                }))
                ->when($f === 'a_recevoir', fn ($q) => $q->whereIn('status', ['commande', 'recue_partielle']))
                ->when($f === 'brouillons', fn ($q) => $q->where('status', 'brouillon'))
                ->latest('id')->paginate(15)->withQueryString();
        }

        if ($tab === 'factures') {
            $invoices = SupplierInvoice::with('supplier')
                ->when($s !== '', fn ($q) => $q->where(function ($w) use ($s) {
                    $w->where('supplier_number', 'like', "%{$s}%")
                      ->orWhereHas('supplier', fn ($c) => $c->where('name', 'like', "%{$s}%"));
                }))
                ->when($f === 'a_payer', fn ($q) => $q->open())
                ->when($f === 'en_retard', fn ($q) => $q->open()->whereDate('due_date', '<', today()))
                ->when($f === 'payees', fn ($q) => $q->where('status', 'payee'))
                ->when(in_array($f, ['a_payer', 'en_retard'], true),
                    fn ($q) => $q->orderBy('due_date')->orderBy('id'),
                    fn ($q) => $q->latest('id'))
                ->paginate(15)->withQueryString();
        }

        if ($tab === 'fournisseurs') {
            $suppliers = Supplier::query()
                ->when($s !== '', fn ($q) => $q->where(function ($w) use ($s) {
                    $w->where('name', 'like', "%{$s}%")
                      ->orWhere('company', 'like', "%{$s}%")
                      ->orWhere('phone', 'like', "%{$s}%");
                }))
                ->orderBy('name')->paginate(15)->withQueryString();

            $dues = SupplierInvoice::open()
                ->whereIn('supplier_id', $suppliers->pluck('id'))
                ->selectRaw('supplier_id, SUM(total_ttc - amount_paid) as due')
                ->groupBy('supplier_id')
                ->pluck('due', 'supplier_id');
        }

        return view('purchases.index', compact('tab', 'f', 'stats', 'counts', 'orders', 'invoices', 'suppliers', 'dues'));
    }
}
