<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ProspectCsvImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DiscoveryCsvUploadController extends Controller
{
    public function store(Request $request, ProspectCsvImportService $importer): RedirectResponse
    {
        $data = $request->validate([
            'csv' => ['required', 'file', 'mimes:csv,txt', 'max:1024'],
            'source' => ['required', 'regex:/^[a-zA-Z0-9_-]{2,40}$/'],
            'dry_run' => ['nullable', 'boolean'],
        ]);
        $directory = storage_path('app/prospect-imports');
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw ValidationException::withMessages(['csv' => 'Dossier d’import inaccessible.']);
        }
        $filename = 'upload-' . Str::random(20) . '.csv';
        $data['csv']->move($directory, $filename);
        try {
            $report = $importer->import($filename, $data['source'], $request->boolean('dry_run'), 300);
        } catch (\Throwable $exception) {
            // Show a safe, generic user error; do not expose filesystem / SQL messages.
            throw ValidationException::withMessages(['csv' => 'Import impossible. Vérifiez le format CSV et les colonnes.']);
        } finally {
            @unlink($directory . DIRECTORY_SEPARATOR . $filename);
        }
        $message = ($report['dry_run'] ? 'Simulation' : 'Import') . ': ' . $report['new'] . ' nouveaux, ' .
            $report['existing'] . ' doublons, ' . $report['in_crm'] . ' déjà dans le CRM, ' . $report['invalid'] . ' invalides.';
        if ($report['truncated']) $message .= ' Limite de 300 lignes atteinte.';
        return redirect()->route('prospects.index')->with('success', $message);
    }
}
