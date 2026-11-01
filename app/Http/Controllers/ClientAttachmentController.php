<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ClientAttachmentController extends Controller
{
    public function store(Request $request, Client $client)
    {
        Gate::authorize('update', $client);

        $request->validate([
            'files'   => ['required', 'array', 'max:20'],
            'files.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'], // 5 Mo / fichier
        ]);

        foreach ($request->file('files') as $file) {
            $path = $file->store("clients/{$client->id}", 'local'); // storage/app/private

            $client->attachments()->create([
                'user_id'       => auth()->id(),
                'original_name' => $file->getClientOriginalName(),
                'path'          => $path,
                'mime_type'     => $file->getMimeType(),
                'size'          => $file->getSize(),
            ]);
        }

        return back()->with('success', 'Fichier(s) ajouté(s).');
    }

    public function show(ClientAttachment $attachment)
    {
        $this->authorizeAttachment($attachment);

        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->response(
            $attachment->path,
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type]
        );
    }

    public function destroy(ClientAttachment $attachment)
    {
        $this->authorizeAttachment($attachment);

        Storage::disk('local')->delete($attachment->path);
        $attachment->delete();

        return back()->with('success', 'Fichier supprimé.');
    }

    private function authorizeAttachment(ClientAttachment $attachment): void
    {
        // Le Global Scope de Client renvoie 404 si le client n'appartient pas au commercial
        $client = $attachment->client()->firstOrFail();

        Gate::authorize('view', $client);
    }
}
