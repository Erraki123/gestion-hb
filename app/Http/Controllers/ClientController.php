<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $clients = Client::with('commercial')
            ->when($request->q, fn ($q, $s) => $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")
                  ->orWhere('company', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            }))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when(auth()->user()->isAdmin() && $request->commercial_id,
                fn ($q, $id) => $q->where('commercial_id', $id))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $commerciaux = auth()->user()->isAdmin()
            ? User::where('role', 'commercial')->orderBy('name')->get()
            : collect();

        return view('clients.index', compact('clients', 'commerciaux'));
    }

    public function create()
    {
        $commerciaux = User::where('role', 'commercial')->where('is_active', true)->orderBy('name')->get();

        return view('clients.create', compact('commerciaux'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $request->validate([
            'photos'   => ['nullable', 'array', 'max:3'],
            'photos.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        // Le commercial est toujours le responsable de ses propres clients
        $data['commercial_id'] = auth()->user()->isAdmin()
            ? $request->validate(['commercial_id' => ['required', 'exists:users,id']])['commercial_id']
            : auth()->id();

        $client = DB::transaction(function () use ($data, $request) {
            $client = Client::create($data);

            foreach ((array) $request->file('photos', []) as $file) {
                if (! $file) {
                    continue; // champ photo vide
                }

                $client->attachments()->create([
                    'user_id'       => auth()->id(),
                    'original_name' => $file->getClientOriginalName(),
                    'path'          => $file->store("clients/{$client->id}", 'local'),
                    'mime_type'     => $file->getMimeType(),
                    'size'          => $file->getSize(),
                ]);
            }

            return $client;
        });

        return redirect()->route('clients.show', $client)
            ->with('success', 'Client créé avec succès.');
    }

    public function show(Client $client)
    {
        Gate::authorize('view', $client);

        $client->load(['commercial', 'notes.user', 'activities.user', 'attachments']);

        // Solde dû par le client : factures émises non soldées
        $due = Invoice::where('client_id', $client->id)
            ->where('type', 'facture')
            ->whereIn('status', ['emise', 'partiellement_payee'])
            ->get()
            ->sum(fn ($i) => $i->balance());

        return view('clients.show', compact('client', 'due'));
    }

    public function statement(Client $client)
    {
        Gate::authorize('view', $client);

        $moves = collect();

        Invoice::where('client_id', $client->id)->where('type', 'facture')
            ->whereIn('status', ['emise', 'partiellement_payee', 'payee'])->get()
            ->each(fn ($i) => $moves->push([
                'date' => $i->issue_date->copy(), 'order' => 0,
                'label' => 'Facture ' . $i->number, 'debit' => (float) $i->total_ttc, 'credit' => 0.0,
            ]));

        Payment::with('invoice')->where('client_id', $client->id)->where('status', 'valide')->get()
            ->each(fn ($p) => $moves->push([
                'date' => $p->paid_at->copy(), 'order' => 1,
                'label' => 'Paiement ' . $p->number . ' (' . $p->methodLabel() . ') — ' . $p->invoice?->number,
                'debit' => 0.0, 'credit' => (float) $p->amount,
            ]));

        $balance = 0.0;
        $rows = $moves->sortBy([['date', 'asc'], ['order', 'asc']])->values()
            ->map(function ($m) use (&$balance) {
                $balance = round($balance + $m['debit'] - $m['credit'], 2);

                return $m + ['balance' => $balance];
            });

        return Pdf::loadView('clients.statement', [
            'client' => $client->load('commercial'),
            'rows'   => $rows,
            'balance' => $balance,
        ])->setPaper('a4')->stream('releve-' . $client->id . '.pdf');
    }

    public function edit(Client $client)
    {
        Gate::authorize('update', $client);

        $commerciaux = User::where('role', 'commercial')->orderBy('name')->get();

        return view('clients.edit', compact('client', 'commerciaux'));
    }

    public function update(Request $request, Client $client)
    {
        Gate::authorize('update', $client);

        $data = $this->validated($request);

        if (auth()->user()->isAdmin() && $request->filled('commercial_id')) {
            $data['commercial_id'] = $request->validate([
                'commercial_id' => ['exists:users,id'],
            ])['commercial_id'];
        }

        $client->update($data);

        return redirect()->route('clients.show', $client)
            ->with('success', 'Client mis à jour.');
    }

    public function destroy(Client $client)
    {
        Gate::authorize('delete', $client);

        $client->delete(); // Soft delete

        return redirect()->route('clients.index')
            ->with('success', 'Client supprimé (restaurable par l\'admin).');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'phone'   => ['nullable', 'string', 'max:30'],
            'email'   => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city'    => ['nullable', 'string', 'max:100'],
            'ice'     => ['nullable', 'string', 'max:30'],
            'status'  => ['required', 'in:prospect,actif,inactif'],
            'comment' => ['nullable', 'string', 'max:5000'],
        ]);
    }
}