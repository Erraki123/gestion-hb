<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExcel
{
    public static function download(array $report, string $filename): StreamedResponse
    {
        $ss    = new Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle(mb_substr(str_replace(['\\', '/', '?', '*', ':', '[', ']'], '', $report['title']), 0, 31));

        $cols = $report['columns'];
        $last = Coordinate::stringFromColumnIndex(count($cols));

        // En-tête du document
        self::put($sheet, 1, 1, config('commerce.company.name'), 'text');
        self::put($sheet, 1, 2, $report['title'], 'text');
        self::put($sheet, 1, 3, $report['subtitle'], 'text');
        $sheet->getStyle('A1:A2')->getFont()->setBold(true);
        $sheet->getStyle('A2')->getFont()->setSize(14);

        // Titres des colonnes (ligne 5)
        foreach ($cols as $i => $col) {
            self::put($sheet, $i + 1, 5, $col['label'], 'text');
        }
        $head = "A5:{$last}5";
        $sheet->getStyle($head)->getFont()->setBold(true);
        $sheet->getStyle($head)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE5E7EB');

        // Lignes
        $r = 6;
        foreach ($report['rows'] as $row) {
            foreach ($cols as $i => $col) {
                self::put($sheet, $i + 1, $r, $row[$col['key']] ?? null, $col['type']);
            }
            $r++;
        }
        $lastData = max($r - 1, 6);

        // Ligne de total
        $totals = $report['totals'] ?? [];
        if ($totals && count($report['rows'])) {
            foreach ($cols as $i => $col) {
                if ($i === 0) {
                    self::put($sheet, 1, $r, 'TOTAL', 'text');
                } elseif (isset($totals[$col['key']])) {
                    self::put($sheet, $i + 1, $r, $totals[$col['key']], $col['type']);
                }
            }
            $sheet->getStyle("A{$r}:{$last}{$r}")->getFont()->setBold(true);
            $lastData = $r;
            $r++;
        }

        // Formats numériques par colonne
        foreach ($cols as $i => $col) {
            $letter = Coordinate::stringFromColumnIndex($i + 1);
            $range  = "{$letter}6:{$letter}{$lastData}";

            if ($col['type'] === 'money') {
                $sheet->getStyle($range)->getNumberFormat()->setFormatCode('#,##0.00');
            } elseif ($col['type'] === 'number') {
                $sheet->getStyle($range)->getNumberFormat()->setFormatCode('#,##0.##');
            }
        }

        // Résumé
        if (! empty($report['summary'])) {
            $r++;
            foreach ($report['summary'] as $s) {
                self::put($sheet, 1, $r, $s['label'], 'text');
                self::put($sheet, 2, $r, $s['value'], $s['type'] ?? 'text');
                $sheet->getStyle("A{$r}")->getFont()->setBold(true);
                if (($s['type'] ?? '') === 'money') {
                    $sheet->getStyle("B{$r}")->getNumberFormat()->setFormatCode('#,##0.00');
                }
                $r++;
            }
        }

        if (! empty($report['note'])) {
            $r++;
            self::put($sheet, 1, $r, $report['note'], 'text');
            $sheet->getStyle("A{$r}")->getFont()->setItalic(true);
            $r++;
        }

        self::put($sheet, 1, $r + 1, 'Généré le ' . $report['generated'], 'text');

        foreach (range(1, count($cols)) as $i) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }
        $sheet->freezePane('A6');

        return response()->streamDownload(function () use ($ss) {
            (new Xlsx($ss))->save('php://output');
            $ss->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /** Texte écrit TOUJOURS comme chaîne (jamais comme formule) ; nombres comme vrais nombres */
    private static function put(Worksheet $sheet, int $col, int $row, mixed $v, string $type): void
    {
        $coord = Coordinate::stringFromColumnIndex($col) . $row;

        if (in_array($type, ['money', 'number'], true) && $v !== null && $v !== '') {
            $sheet->setCellValueExplicit($coord, (float) $v, DataType::TYPE_NUMERIC);
        } else {
            $sheet->setCellValueExplicit($coord, (string) ($v ?? ''), DataType::TYPE_STRING);
        }
    }
}