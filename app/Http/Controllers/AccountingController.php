<?php

namespace App\Http\Controllers;

use App\Services\AccountingExport;
use App\Services\ActivityLogger;
use App\Services\ReportService;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    public function index(Request $request, ReportService $service)
    {
        $request->merge([
            'mode' => $request->input('mode', 'month'),
            'month' => $request->input('month', now()->copy()->subMonth()->format('Y-m')),
        ]);

        $period = $service->period($request);

        return view('accounting.index', compact('period'));
    }

    public function download(Request $request, ReportService $service)
    {
        $period = $service->period($request);
        $sheets = AccountingExport::sheetRows($period);
        $data = [];

        foreach ($sheets as $title => $sheet) {
            $data[$title] = count($sheet['rows']);
        }

        ActivityLogger::log('export', 'Export comptable — ' . $period['label'], null, ['data' => $data]);

        $filename = 'accounting-' . $period['from']->format('Ymd') . '-' . $period['to']->format('Ymd') . '.xlsx';

        return AccountingExport::download($period, $filename);
    }
}
