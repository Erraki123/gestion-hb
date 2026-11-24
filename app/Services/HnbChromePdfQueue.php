<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

/**
 * Hostinger: queue the EXACT Blade HTML for the existing Chromium renderer.
 * Browser request never launches Chrome. Only the cron/CLI command does.
 * No template/style changes, and no public PDF paths.
 */
final class HnbChromePdfQueue
{
    public static function directory(): string
    {
        return storage_path('app/hnb-chromium-cli-queue');
    }

    public function respond(Request $request, string $type, int $id, array $doc, string $html)
    {
        $dir = self::directory();
        if (! is_dir($dir)) {
            File::makeDirectory($dir, 0700, true, true);
        }
        abort_unless(is_dir($dir) && is_writable($dir), 503, 'PDF storage unavailable.');

        // Separate users and versions, so no cross-user cache and no stale PDFs.
        $user = $request->user();
        $key = hash('sha256', $user->getAuthIdentifier().'|'.$type.'|'.$id.'|'.hash('sha256', $html));
        $base = $dir.DIRECTORY_SEPARATOR.$key;
        $pdf = $base.'.pdf';
        $pending = $base.'.queued';
        $failure = $base.'.failed';
        $source = $base.'.html';
        $name = preg_replace('/[^A-Za-z0-9_.-]/', '_', (string) ($doc['number'] ?: "$type-$id")).'.pdf';

        if (is_file($pdf) && filesize($pdf) > 500) {
            $f = fopen($pdf, 'rb');
            $magic = $f ? fread($f, 5) : false;
            if ($f) fclose($f);
            if ($magic === '%PDF-') {
                return response()->download($pdf, $name, [
                    'Content-Type' => 'application/pdf',
                    'Cache-Control' => 'private, no-store',
                    'X-Robots-Tag' => 'noindex, nofollow',
                ]);
            }
        }

        if ($request->query('retry_pdf') === '1' && is_file($failure)) {
            File::delete($failure);
        }
        $failed = is_file($failure);
        if (! $failed && ! is_file($pending)) {
            // Write HTML before marking it ready for the CLI worker.
            File::put($source, $html, true);
            @chmod($source, 0600);
            File::put($pending, now()->toIso8601String(), true);
            @chmod($pending, 0600);
        }

        // HNB_PDF_AUTO_RETURN_V1: retain original Chromium PDF and queue,
        // but download from this page asynchronously and leave the waiting screen.
        $url = url()->current().'?download=1';
        $retryUrl = $url.'&retry_pdf=1';
        $previewUrl = url()->current().'?download=0';
        $title = $failed ? 'Échec de la génération PDF' : 'Préparation de votre PDF HN BLOOM';
        $message = $failed
            ? 'Le moteur Chromium n’a pas terminé. Relancez la génération ou contactez votre administrateur.'
            : 'Votre document est en cours de préparation. Le téléchargement démarrera automatiquement, puis vous reviendrez à votre page.';

        // HEX escaping prevents a document number/URL from closing a <script> tag.
        $browserConfig = json_encode([
            'downloadUrl' => $url,
            'previewUrl' => $previewUrl,
            'filename' => $name,
            'failed' => $failed,
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES);
        $safeTitle = e($title);
        $safeMessage = e($message);
        $safeRetryUrl = e($retryUrl);
        $safePreviewUrl = e($previewUrl);
        $primaryAction = $failed
            ? '<a class="action" href="'.$safeRetryUrl.'">Réessayer</a>'
            : '<button class="action" id="hnb-pdf-check" type="button">Vérifier le téléchargement</button>';

        $page = <<<HTML
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{$safeTitle}</title>
    <style>
        body{margin:0;min-height:100vh;display:grid;place-items:center;background:#0b0b0b;color:#f7f1e7;font:16px Arial,sans-serif}
        main{max-width:520px;margin:24px;padding:32px;border:1px solid #ad8847;border-radius:12px;text-align:center}
        h1{color:#d7ad51;font-size:26px}p{line-height:1.7}
        .action{display:inline-block;margin:12px 4px 0;padding:12px 18px;border:0;border-radius:6px;background:#c79a2e;color:#111;text-decoration:none;font-weight:bold;cursor:pointer;font:inherit}
        .back{display:inline-block;margin:12px 4px 0;padding:12px 18px;border:1px solid #ad8847;border-radius:6px;color:#f7f1e7;text-decoration:none}
        #hnb-status{font-size:13px;color:#d8c6a5;min-height:1.5em}
    </style>
</head>
<body>
    <main>
        <h1>HN BLOOM</h1>
        <h2>{$safeTitle}</h2>
        <p>{$safeMessage}</p>
        <p id="hnb-status" role="status" aria-live="polite"></p>
        {$primaryAction}
        <a class="back" href="{$safePreviewUrl}">Retour au document</a>
    </main>
    <script>
    (() => {
        'use strict';
        const options = {$browserConfig};
        if (options.failed) return;
        const status = document.getElementById('hnb-status');
        const check = document.getElementById('hnb-pdf-check');
        let busy = false;
        let finished = false;
        let waitTimer = null;

        function returnAddress() {
            // Only return to an actual previous page on the same site.
            // Otherwise show the document preview (never the waiting URL).
            try {
                const previous = new URL(document.referrer);
                if (previous.origin === window.location.origin &&
                    !(previous.pathname === window.location.pathname && previous.searchParams.has('download'))) {
                    return previous.href;
                }
            } catch (ignored) {}
            return options.previewUrl;
        }

        async function checkPdf() {
            if (busy || finished) return;
            busy = true;
            try {
                const reply = await fetch(options.downloadUrl, {
                    method: 'GET', credentials: 'same-origin', cache: 'no-store',
                    headers: {'Accept': 'application/pdf, text/html'}
                });
                const kind = (reply.headers.get('content-type') || '').toLowerCase();
                if (reply.status === 202) {
                    status.textContent = 'Votre PDF est en cours de génération…';
                    return;
                }
                if (reply.ok && kind.includes('application/pdf')) {
                    const blob = await reply.blob();
                    if (blob.size < 500) throw new Error('Fichier PDF incomplet.');
                    finished = true;
                    const pdfUrl = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = pdfUrl;
                    a.download = options.filename;
                    a.style.display = 'none';
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    status.textContent = 'PDF téléchargé. Retour automatique…';
                    // Delay lets the browser start saving the already-loaded Blob.
                    window.setTimeout(() => window.location.replace(returnAddress()), 2200);
                    return;
                }
                if (reply.status === 401 || reply.status === 403 ||
                    (reply.status === 200 && kind.includes('text/html'))) {
                    finished = true;
                    status.textContent = 'Impossible de télécharger automatiquement. Vérifiez votre session ou réessayez.';
                    return;
                }
                status.textContent = 'Téléchargement indisponible. Nouvelle tentative…';
            } catch (error) {
                status.textContent = 'Connexion interrompue. Nouvelle tentative…';
            } finally {
                busy = false;
                if (!finished) waitTimer = window.setTimeout(checkPdf, 4500);
            }
        }
        check.addEventListener('click', () => {
            window.clearTimeout(waitTimer);
            checkPdf();
        });
        // No navigation to an attachment URL: that leaves the waiting tab behind.
        window.setTimeout(checkPdf, 1100);
    })();
    </script>
</body>
</html>
HTML;

        return response($page, $failed ? 200 : 202)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'private, no-store')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
