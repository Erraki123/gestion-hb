<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiscoveredProspect;
use App\Services\AtlasContactDecisionService;
use App\Services\AtlasContactReviewService;
use Illuminate\Http\Request;

final class AtlasContactDecisionController extends Controller
{
    public function store(Request $request, DiscoveredProspect $candidate, AtlasContactReviewService $contacts, AtlasContactDecisionService $decisions)
    {
        $data = $request->validate([
            'decision' => ['required','in:reviewed,rejected'],
            'source_checked' => ['nullable','accepted'],
            'fingerprint' => ['required','string','size:64','regex:/^[a-f0-9]{64}$/'],
        ]);
        if ($data['decision'] === 'reviewed' && !$request->boolean('source_checked')) {
            return back()->withErrors(['contact_review' => 'Veuillez consulter la source avant de marquer les coordonnées comme contrôlées.']);
        }
        if ((string)$candidate->status !== 'pending') {
            abort(409, 'Cette suggestion n’est plus en attente.');
        }
        $suggestion = $contacts->forCompany((string)$candidate->company, (string)$candidate->city, (string)$candidate->source_url);
        if (!$suggestion || !hash_equals($decisions->fingerprint($suggestion), $data['fingerprint'])) {
            abort(409, 'Les données proposées ont changé. Rechargez la page.');
        }
        $decisions->record($candidate, $suggestion, $data['decision'], (int)$request->user()->id);
        return back()->with('contact_review_message', $data['decision'] === 'reviewed'
            ? 'Source consultée. Coordonnées contrôlées manuellement (non confirmées auprès de l’entreprise).'
            : 'Suggestion de contact écartée.');
    }
}
