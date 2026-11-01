<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ClientNoteController extends Controller
{
    public function store(Request $request, Client $client)
    {
        Gate::authorize('update', $client);

        $data = $request->validate(['content' => ['required', 'string', 'max:5000']]);

        $client->notes()->create([
            'user_id' => auth()->id(),
            'content' => $data['content'],
        ]);

        return back()->with('success', 'Note ajoutée.');
    }
}
