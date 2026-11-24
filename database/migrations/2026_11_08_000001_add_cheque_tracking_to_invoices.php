<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) enum -> string, pour accepter la nouvelle valeur 'cheque_en_attente'
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('status', 30)->default('brouillon')->change();
        });

        // 2) deux montants stockés
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('amount_cashed', 12, 2)->default(0)->after('amount_paid');
            $table->decimal('amount_cheque_pending', 12, 2)->default(0)->after('amount_cashed');
        });

        // 3) backfill des factures existantes
        $rows = DB::table('payments')
            ->where('status', 'valide')
            ->selectRaw("invoice_id,
                SUM(amount) AS paid,
                SUM(CASE WHEN method = 'cheque' AND cheque_status = 'en_attente' THEN amount ELSE 0 END) AS pending")
            ->groupBy('invoice_id')
            ->get();

        foreach ($rows as $r) {
            $pending = round((float) $r->pending, 2);
            $cashed  = round((float) $r->paid - $pending, 2);

            DB::table('invoices')->where('id', $r->invoice_id)->update([
                'amount_cashed'         => $cashed,
                'amount_cheque_pending' => $pending,
            ]);

            // Facture entièrement couverte mais avec un chèque non encaissé
            if ($pending > 0.005) {
                DB::table('invoices')
                    ->where('id', $r->invoice_id)
                    ->where('status', 'payee')
                    ->update(['status' => 'cheque_en_attente']);
            }
        }
    }

    public function down(): void
    {
        DB::table('invoices')->where('status', 'cheque_en_attente')->update(['status' => 'payee']);

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['amount_cashed', 'amount_cheque_pending']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->enum('status', ['brouillon', 'emise', 'partiellement_payee', 'payee', 'annulee'])
                ->default('brouillon')->change();
        });
    }
};
