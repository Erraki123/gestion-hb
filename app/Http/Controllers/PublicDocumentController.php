<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\DocumentReleaseApproval;
use App\Services\DocumentSender;
use Illuminate\Http\Request;

/** The signed URL is tied to one approved snapshot, never to a mutable document ID alone. */
class PublicDocumentController extends Controller
{
    public function show(Request $request, string $type, int $id, DocumentReleaseApproval $approval)
    {
        $doc = DocumentSender::find($type, $id);
        abort_unless($doc, 404);

        if ($doc instanceof Invoice) {
            abort_unless($doc->type === 'facture' && $doc->number, 404);
        }

        // Existing legacy signed URLs without a version are intentionally rejected.
        $version = $request->query('v');
        abort_unless(is_string($version) && preg_match('/\A[a-f0-9]{64}\z/D', $version), 403);
        abort_unless($approval->canRelease($doc), 403);
        abort_unless(hash_equals($approval->fingerprint($doc), $version), 403);

        return response(DocumentSender::pdf($type, $doc), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . DocumentSender::filename($type, $doc) . '"',
            'X-Robots-Tag' => 'noindex',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
