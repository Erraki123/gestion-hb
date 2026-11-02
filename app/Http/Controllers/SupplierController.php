<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::withTrashed()->orderBy('name')->paginate(15);

        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('suppliers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'ice' => ['nullable', 'string', 'max:50'],
            'payment_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $data['payment_days'] = (int) ($data['payment_days'] ?? 30);
        $data['is_active'] = $request->boolean('is_active', true);

        $supplier = Supplier::create($data);

        return redirect()->route('suppliers.show', $supplier)->with('success', 'Fournisseur créé.');
    }

    public function show(Supplier $supplier)
    {
        $supplier->load(['purchaseOrders', 'goodsReceipts', 'invoices.payments']);

        return view('suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'ice' => ['nullable', 'string', 'max:50'],
            'payment_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $supplier->update($data);

        return redirect()->route('suppliers.show', $supplier)->with('success', 'Fournisseur mis à jour.');
    }

    public function destroy(Supplier $supplier)
    {
        DB::transaction(function () use ($supplier) {
            $supplier->delete();
        });

        return redirect()->route('suppliers.index')->with('success', 'Fournisseur supprimé.');
    }
}
