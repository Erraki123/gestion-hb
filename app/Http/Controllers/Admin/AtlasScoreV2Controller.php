<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

final class AtlasScoreV2Controller extends Controller
{
    public function index(Request $request)
    {
        $path = storage_path('app/prospect-imports/atlas-smart-score-v2.csv');
        $items = [];
        $error = null;
        $fileDate = null;
        if (!is_file($path)) {
            $error = 'Rapport absent. Exécutez : python .\\tools\\atlas_smart_score.py';
        } elseif (filesize($path) > 8 * 1024 * 1024) {
            $error = 'Rapport trop volumineux pour cet aperçu.';
        } else {
            $fileDate = date('Y-m-d H:i', filemtime($path));
            $handle = fopen($path, 'rb');
            if ($handle === false) {
                $error = 'Impossible de lire le rapport local.';
            } else {
                try {
                    $headers = fgetcsv($handle, 0, ',', '"', '');
                    if (!is_array($headers)) {
                        $error = 'Le rapport est vide.';
                    } else {
                        $headers = array_map(static fn ($s) => trim((string) $s, "\xEF\xBB\xBF \t\r\n"), $headers);
                        $required = ['company', 'city', 'sector_v2', 'opportunity_priority', 'data_confidence'];
                        if (array_diff($required, $headers)) {
                            $error = 'Colonnes manquantes dans le rapport Smart Scoring V2.';
                        } else {
                            while (($line = fgetcsv($handle, 0, ',', '"', '')) !== false && count($items) < 2000) {
                                if (count($line) !== count($headers)) {
                                    continue;
                                }
                                $items[] = array_combine($headers, $line);
                            }
                        }
                    }
                } finally {
                    fclose($handle);
                }
            }
        }
        $search = mb_strtolower(trim((string) $request->query('q', '')));
        $sector = trim((string) $request->query('sector', ''));
        $allowed = ['sante', 'hotellerie', 'banque', 'commerce', 'enseignement', 'industrie', 'partenaire_securite', 'non_determine'];
        if (!in_array($sector, $allowed, true)) {
            $sector = '';
        }
        $items = array_values(array_filter($items, static function ($r) use ($search, $sector) {
            $matches = $search === '' || str_contains(mb_strtolower(($r['company'] ?? '') . ' ' . ($r['city'] ?? '')), $search);
            return $matches && ($sector === '' || ($r['sector_v2'] ?? '') === $sector);
        }));
        $page = max(1, min(10000, (int) $request->query('page', 1)));
        $total = count($items);
        $items = array_slice($items, ($page - 1) * 25, 25);
        return View::make('admin.atlas.score-v2', compact('items', 'error', 'fileDate', 'total', 'page', 'search', 'sector', 'allowed'));
    }
}
