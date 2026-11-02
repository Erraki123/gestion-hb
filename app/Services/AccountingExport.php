<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountingExport
{
    public static function download(array $period, string $filename): StreamedResponse
    {
        $workbook = new Spreadsheet();
        $sheets = self::sheetRows($period);
        $order = ['Ventes', 'Encaissements', 'Achats', 'Règlements fournisseurs', 'Récap TVA'];

        foreach ($order as $index => $title) {
            $sheet = $index === 0 ? $workbook->getActiveSheet() : $workbook->createSheet();
            $sheet->setTitle(mb_substr($title, 0, 31));
            self::writeSheet($sheet, $title, $period, $sheets[$title]['columns'], $sheets[$title]['rows']);
        }

        $workbook->setActiveSheetIndex(0);

        return response()->streamDownload(function () use ($workbook) {
            (new Xlsx($workbook))->save('php://output');
            $workbook->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public static function sheetRows(array $period): array
    {
        return [
            'Ventes' => [
                'columns' => ['Date', 'Type', 'N° pièce', 'Client', 'ICE client', 'Taux TVA', 'Base HT', 'TVA', 'TTC'],
                'rows' => self::salesRows($period),
            ],
            'Encaissements' => [
                'columns' => ['Date', 'N° paiement', 'N° facture', 'Client', 'Mode', 'N° chèque', 'Référence', 'Montant'],
                'rows' => self::paymentRows($period),
            ],
            'Achats' => [
                'columns' => ['Date', 'N° pièce fournisseur', 'Fournisseur', 'ICE', 'Taux TVA', 'Base HT', 'TVA', 'TTC'],
                'rows' => self::purchaseRows($period),
            ],
            'Règlements fournisseurs' => [
                'columns' => ['Date', 'N° paiement', 'N° pièce', 'Fournisseur', 'Mode', 'Référence', 'Montant'],
                'rows' => self::supplierPaymentRows($period),
            ],
            'Récap TVA' => [
                'columns' => ['Taux TVA', 'Base ventes', 'TVA collectée', 'Base achats', 'TVA déductible', 'Net TVA'],
                'rows' => self::vatSummaryRows($period),
            ],
        ];
    }

    private static function writeSheet(Worksheet $sheet, string $title, array $period, array $columns, array $rows): void
    {
        $company = config('commerce.company.name');
        $generated = 'Généré le ' . Carbon::now()->format('d/m/Y H:i') . ' — ' . (auth()->user()->name ?? '');

        self::put($sheet, 1, 1, $company, 'text');
        self::put($sheet, 1, 2, $title, 'text');
        self::put($sheet, 1, 3, $period['label'], 'text');
        self::put($sheet, 1, 4, $generated, 'text');

        $sheet->getStyle('A1:A4')->getFont()->setBold(true);
        $sheet->getStyle('A2')->getFont()->setSize(14);

        foreach ($columns as $index => $column) {
            self::put($sheet, $index + 1, 5, $column, 'text');
        }

        $headerRange = 'A5:' . Coordinate::stringFromColumnIndex(count($columns)) . '5';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE5E7EB');

        $rowIndex = 6;
        foreach ($rows as $row) {
            foreach ($columns as $index => $column) {
                $value = $row[$column] ?? null;
                $isNumeric = in_array($column, ['Taux TVA', 'Base HT', 'TVA', 'TTC', 'Montant', 'Base ventes', 'TVA collectée', 'Base achats', 'TVA déductible', 'Net TVA'], true);
                self::put($sheet, $index + 1, $rowIndex, $value, $isNumeric ? 'number' : 'text');
            }
            $rowIndex++;
        }

        foreach (range(1, count($columns)) as $index) {
            $letter = Coordinate::stringFromColumnIndex($index);
            $sheet->getColumnDimension($letter)->setAutoSize(true);
            $sheet->getStyle($letter . '6:' . $letter . max($rowIndex - 1, 6))->getNumberFormat()->setFormatCode('#,##0.00');
        }

        $sheet->freezePane('A6');
    }

    private static function salesRows(array $period): array
    {
        if (! Invoice::query()->exists()) {
            return [];
        }

        $invoices = Invoice::with(['client', 'items'])
            ->whereBetween('issue_date', [$period['from']->toDateString(), $period['to']->toDateString()])
            ->whereNotNull('number')
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('type', 'facture')
                        ->whereIn('status', ['emise', 'partiellement_payee', 'payee', 'annulee']);
                })->orWhere('type', 'avoir');
            })
            ->orderBy('issue_date')
            ->orderBy('id')
            ->get();

        $rows = [];

        foreach ($invoices as $invoice) {
            $sign = $invoice->type === 'avoir' ? -1 : 1;
            $breakdown = DocumentTotals::breakdown($invoice->items);

            foreach ($breakdown as $line) {
                $base = round((float) $line['base'] * $sign, 2);
                $tax  = round((float) $line['tax'] * $sign, 2);
                $rows[] = [
                    'Date' => $invoice->issue_date?->format('d/m/Y') ?? '',
                    'Type' => $invoice->type === 'avoir' ? 'Avoir' : 'Facture',
                    'N° pièce' => $invoice->number,
                    'Client' => $invoice->client?->name ?? '',
                    'ICE client' => $invoice->client?->ice ?? '',
                    'Taux TVA' => (float) $line['rate'],
                    'Base HT' => $base,
                    'TVA' => $tax,
                    'TTC' => round($base + $tax, 2),
                ];
            }
        }

        return $rows;
    }

    private static function paymentRows(array $period): array
    {
        return Payment::with(['invoice', 'client'])
            ->where('status', 'valide')
            ->whereBetween('paid_at', [$period['from']->toDateString(), $period['to']->toDateString()])
            ->orderBy('paid_at')
            ->orderBy('id')
            ->get()
            ->map(fn ($payment) => [
                'Date' => $payment->paid_at?->format('d/m/Y') ?? '',
                'N° paiement' => $payment->number,
                'N° facture' => $payment->invoice?->number ?? '',
                'Client' => $payment->client?->name ?? '',
                'Mode' => $payment->methodLabel(),
                'N° chèque' => $payment->cheque_number ?? '',
                'Référence' => $payment->reference ?? '',
                'Montant' => round((float) $payment->amount, 2),
            ])
            ->all();
    }

    private static function purchaseRows(array $period): array
    {
        if (! Schema::hasTable('supplier_invoices') || ! Schema::hasTable('supplier_invoice_lines')) {
            return [];
        }

        $rows = [];
        $invoices = SupplierInvoice::with(['supplier', 'lines'])
            ->whereBetween('invoice_date', [$period['from']->toDateString(), $period['to']->toDateString()])
            ->where('status', '!=', 'annulee')
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get();

        foreach ($invoices as $invoice) {
            foreach ($invoice->lines as $line) {
                $base = round((float) $line->base_ht, 2);
                $tax  = round((float) $line->tax_amount, 2);
                $rows[] = [
                    'Date' => $invoice->invoice_date?->format('d/m/Y') ?? '',
                    'N° pièce fournisseur' => $invoice->supplier_number,
                    'Fournisseur' => $invoice->supplier?->name ?? '',
                    'ICE' => $invoice->supplier?->ice ?? '',
                    'Taux TVA' => (float) $line->tax_rate,
                    'Base HT' => $base,
                    'TVA' => $tax,
                    'TTC' => round($base + $tax, 2),
                ];
            }
        }

        return $rows;
    }

    private static function supplierPaymentRows(array $period): array
    {
        if (! Schema::hasTable('supplier_payments')) {
            return [];
        }

        return SupplierPayment::with(['supplierInvoice', 'supplier'])
            ->where('status', 'valide')
            ->whereBetween('paid_at', [$period['from']->toDateString(), $period['to']->toDateString()])
            ->orderBy('paid_at')
            ->orderBy('id')
            ->get()
            ->map(fn ($payment) => [
                'Date' => $payment->paid_at?->format('d/m/Y') ?? '',
                'N° paiement' => $payment->number,
                'N° pièce' => $payment->supplierInvoice?->supplier_number ?? '',
                'Fournisseur' => $payment->supplier?->name ?? '',
                'Mode' => $payment->method ?? '',
                'Référence' => $payment->reference ?? '',
                'Montant' => round((float) $payment->amount, 2),
            ])
            ->all();
    }

    private static function vatSummaryRows(array $period): array
    {
        $rates = [];

        foreach (self::salesRows($period) as $row) {
            $rate = (string) number_format((float) $row['Taux TVA'], 2, '.', '');
            $rates[$rate]['sales_base'] = ($rates[$rate]['sales_base'] ?? 0.0) + (float) $row['Base HT'];
            $rates[$rate]['sales_tax'] = ($rates[$rate]['sales_tax'] ?? 0.0) + (float) $row['TVA'];
        }

        foreach (self::purchaseRows($period) as $row) {
            $rate = (string) number_format((float) $row['Taux TVA'], 2, '.', '');
            $rates[$rate]['purchase_base'] = ($rates[$rate]['purchase_base'] ?? 0.0) + (float) $row['Base HT'];
            $rates[$rate]['purchase_tax'] = ($rates[$rate]['purchase_tax'] ?? 0.0) + (float) $row['TVA'];
        }

        ksort($rates, SORT_NUMERIC);

        $summary = [];
        $net = 0.0;
        foreach ($rates as $rate => $values) {
            $salesBase = round((float) ($values['sales_base'] ?? 0.0), 2);
            $salesTax  = round((float) ($values['sales_tax'] ?? 0.0), 2);
            $purchaseBase = round((float) ($values['purchase_base'] ?? 0.0), 2);
            $purchaseTax  = round((float) ($values['purchase_tax'] ?? 0.0), 2);
            $net += $salesTax - $purchaseTax;

            $summary[] = [
                'Taux TVA' => (float) $rate,
                'Base ventes' => $salesBase,
                'TVA collectée' => $salesTax,
                'Base achats' => $purchaseBase,
                'TVA déductible' => $purchaseTax,
                'Net TVA' => round($salesTax - $purchaseTax, 2),
            ];
        }

        $summary[] = [
            'Taux TVA' => 'Indicatif (à valider par le comptable)',
            'Base ventes' => null,
            'TVA collectée' => null,
            'Base achats' => null,
            'TVA déductible' => null,
            'Net TVA' => round($net, 2),
        ];

        return $summary;
    }

    private static function put(Worksheet $sheet, int $col, int $row, mixed $value, string $type): void
    {
        $coordinate = Coordinate::stringFromColumnIndex($col) . $row;

        if (in_array($type, ['number'], true) && $value !== null && $value !== '') {
            $sheet->setCellValueExplicit($coordinate, (float) $value, DataType::TYPE_NUMERIC);
            return;
        }

        $sheet->setCellValueExplicit($coordinate, (string) ($value ?? ''), DataType::TYPE_STRING);
    }
}
