<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ReportExcel;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use App\Services\ActivityLogger;
class ReportController extends Controller
{
    private const PDF_MAX_ROWS = 1500;

    public function index()
    {
        return view('reports.index', [
            'types' => ReportService::available(auth()->user()->isAdmin()),
        ]);
    }

    public function show(Request $request, string $type, ReportService $service)
    {
        [$report, $period, $meta] = $this->prepare($request, $type, $service);

        $commerciaux = (auth()->user()->isAdmin() && $meta['commercial'])
            ? User::where('role', 'commercial')->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('reports.show', compact('report', 'period', 'meta', 'type', 'commerciaux'));
    }

    public function export(Request $request, string $type, string $format, ReportService $service)
    {
        [$report, $period, $meta] = $this->prepare($request, $type, $service);

        $name = $type . '_' . ($meta['period']
            ? $period['from']->format('Ymd') . '-' . $period['to']->format('Ymd')
            : now()->format('Ymd'));
ActivityLogger::log('export', 'Export ' . strtoupper($format) . ' — ' . $report['title'], null, [
    'data' => [
        'rapport' => $type,
        'format'  => $format,
        'période' => $report['subtitle'],
        'lignes'  => count($report['rows']),
    ],
]);
        if ($format === 'pdf') {
            if (count($report['rows']) > self::PDF_MAX_ROWS) {
                return back()->withErrors([
                    'export' => 'Trop de lignes pour un PDF (' . count($report['rows']) . '). Utilisez l\'export Excel ou réduisez la période.',
                ]);
            }

            return Pdf::loadView('reports.pdf', compact('report'))
                ->setPaper('a4', 'landscape')
                ->download($name . '.pdf');
        }

        return ReportExcel::download($report, $name . '.xlsx');
    }

    private function prepare(Request $request, string $type, ReportService $service): array
    {
        $isAdmin = auth()->user()->isAdmin();

        abort_unless(isset(ReportService::TYPES[$type]), 404);
        abort_unless(ReportService::allowed($type, $isAdmin), 403);

        $meta = ReportService::TYPES[$type];

        // ⭐ Le commercial est TOUJOURS limité à lui-même ; le paramètre de l'URL est ignoré
        $userId = null;
        if ($meta['commercial']) {
            $userId = $isAdmin ? ($request->integer('commercial_id') ?: null) : auth()->id();

            if ($isAdmin && $userId && ! User::where('role', 'commercial')->whereKey($userId)->exists()) {
                $userId = null;
            }
        }

        $period = $service->period($request);
        $report = $service->build($type, $period, $userId, $isAdmin);

        return [$report, $period, $meta];
    }
}