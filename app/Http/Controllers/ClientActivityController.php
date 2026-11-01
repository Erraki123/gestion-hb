<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ClientActivityController extends Controller
{
    public function store(Request $request, Client $client)
    {
        Gate::authorize('update', $client);

        $data = $request->validate([
            'type'        => ['required', 'in:appel,visite,email,autre'],
            'description' => ['nullable', 'string', 'max:2000'],
            'happened_at' => ['required', 'date'],
        ]);

        $client->activities()->create($data + ['user_id' => auth()->id()]);

        return back()->with('success', 'Activité enregistrée.');
    }
}
