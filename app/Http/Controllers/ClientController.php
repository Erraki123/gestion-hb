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
use Illuminate\Support\Facades\Storage;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $isAdmin = auth()->user()->isAdmin();
        $term    = trim((string) ($request->input('name') ?: $request->input('q')));
        $phone   = trim((string) $request->input('phone'));
        $city    = trim((string) $request->input('city'));
        $status  = in_array($request->input('status'), ['prospect', 'actif', 'inactif'], true)
            ? $request->input('status') : null;

        // Le scope global « owner » limite déjà le commercial à ses propres clients
        $clients = Client::with('commercial')
            ->when($term !== '', function ($q) use ($term) {
                $like = '%' . $this->escapeLike($term) . '%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)
                    ->orWhere('company', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('email', 'like', $like));
            })
            ->when($phone !== '', fn ($q) => $q->where('phone', 'like', '%' . $this->escapeLike($phone) . '%'))
            ->when($city !== '', fn ($q) => $q->where('city', 'like', '%' . $this->escapeLike($city) . '%'))
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->when($isAdmin && $request->filled('commercial_id'),
                fn ($q) => $q->where('commercial_id', (int) $request->input('commercial_id')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // Appel AJAX du filtre : on renvoie seulement les lignes et la pagination
        if ($request->expectsJson()) {
            return response()->json([
                'rows'  => view('clients._rows', ['clients' => $clients])->render(),
                'links' => (string) $clients->links(),
                'total' => $clients->total(),
            ]);
        }

        $commerciaux = $isAdmin
            ? User::where('role', 'commercial')->orderBy('name')->get()
            : collect();

        return view('clients.index', compact('clients', 'commerciaux'));
    }

    /**
     * Recherche rapide (AJAX) pour le champ <x-select-search>.
     * Format générique : [{id, label, sub}] — 20 résultats maximum.
     */
    public function search(Request $request)
    {
        $s = trim((string) $request->query('q', ''));

        $clients = Client::query()
            ->when($s !== '', function ($q) use ($s) {
                $like = '%' . $this->escapeLike($s) . '%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)
                    ->orWhere('company', 'like', $like)
                    ->orWhere('phone', 'like', $like));
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'company', 'phone', 'city']);

        return response()->json($clients->map(fn ($c) => [
            'id'    => $c->id,
            'label' => $c->name,
            'sub'   => collect([$c->company, $c->city, $c->phone])->filter()->implode(' · '),
        ])->values());
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
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

        DB::transaction(function () use ($data, $request) {
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
        });

        // Après l'enregistrement : retour à la table des clients
        return redirect()->route('clients.index')
            ->with('success', 'Client créé avec succès.');
    }

    public function show(Client $client)
    {
        Gate::authorize('view', $client);

        $client->load(['commercial', 'notes.user', 'activities.user', 'attachments']);

        // Solde dû par le client : factures émises non soldées
        $due = Invoice::where('client_id', $client->id)
            ->where('type', 'facture')
            ->whereIn('status', Invoice::OPEN)
            ->get()
            ->sum(fn ($i) => $i->balance());

        return view('clients.show', compact('client', 'due'));
    }

    public function statement(Client $client)
    {
        Gate::authorize('view', $client);

        $moves = collect();

        Invoice::where('client_id', $client->id)->where('type', 'facture')
            ->whereIn('status', Invoice::COUNTED)->get()
            ->each(fn ($i) => $moves->push([
                'date' => $i->issue_date->copy(), 'order' => 0,
                'label' => 'Facture ' . $i->number, 'debit' => (float) $i->total_ttc, 'credit' => 0.0,
            ]));

        Invoice::where('client_id', $client->id)
            ->partialCredits()
            ->get()
            ->each(fn ($i) => $moves->push([
                'date' => $i->issue_date->copy(), 'order' => 0,
                'label' => 'Avoir partiel ' . $i->number, 'debit' => 0.0, 'credit' => (float) $i->total_ttc,
            ]));

        Payment::with('invoice')->where('client_id', $client->id)->where('status', 'valide')->get()
            ->each(fn ($p) => $moves->push([
                'date' => $p->paid_at->copy(), 'order' => 1,
                'label' => 'Paiement ' . $p->number . ' (' . $p->methodLabel() . ')'
                    . ($p->cheque_status === 'en_attente' ? ' [chèque non encaissé]' : '')
                    . ' — ' . $p->invoice?->number,
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

        $request->validate([
            'files'    => ['nullable', 'array', 'max:20'],
            'files.*'  => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'], // 5 Mo / fichier
            'remove'   => ['nullable', 'array'],
            'remove.*' => ['integer'],
        ]);

        DB::transaction(function () use ($client, $data, $request) {
            $client->update($data);

            // Fichiers cochés « Supprimer » (uniquement ceux de CE client)
            $toRemove = $client->attachments()
                ->whereIn('id', (array) $request->input('remove', []))
                ->get();

            foreach ($toRemove as $attachment) {
                Storage::disk('local')->delete($attachment->path);
                $attachment->delete();
            }

            // Nouveaux fichiers
            foreach ((array) $request->file('files', []) as $file) {
                if (! $file) {
                    continue;
                }

                $client->attachments()->create([
                    'user_id'       => auth()->id(),
                    'original_name' => $file->getClientOriginalName(),
                    'path'          => $file->store("clients/{$client->id}", 'local'),
                    'mime_type'     => $file->getMimeType(),
                    'size'          => $file->getSize(),
                ]);
            }
        });

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