<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        return redirect()->route('purchases.index', ['tab' => 'fournisseurs']);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['payment_days'] = (int) ($data['payment_days'] ?? 30);
        $data['is_active'] = true;

        Supplier::create($data);

        return redirect()->route('purchases.index', ['tab' => 'fournisseurs'])
            ->with('success', 'Fournisseur créé.');
    }

    public function show(Supplier $supplier)
    {
        $supplier->load(['purchaseOrders', 'invoices.payments']);

        return view('suppliers.show', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $data = $this->validated($request);
        $data['payment_days'] = (int) ($data['payment_days'] ?? $supplier->payment_days ?? 30);

        $supplier->update($data);

        return back()->with('success', 'Fournisseur mis à jour.');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return redirect()->route('purchases.index', ['tab' => 'fournisseurs'])
            ->with('success', 'Fournisseur supprimé.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'company'      => ['nullable', 'string', 'max:255'],
            'phone'        => ['nullable', 'string', 'max:50'],
            'email'        => ['nullable', 'email', 'max:255'],
            'address'      => ['nullable', 'string', 'max:255'],
            'city'         => ['nullable', 'string', 'max:120'],
            'ice'          => ['nullable', 'string', 'max:50'],
            'payment_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'notes'        => ['nullable', 'string'],
        ]);
    }
}
