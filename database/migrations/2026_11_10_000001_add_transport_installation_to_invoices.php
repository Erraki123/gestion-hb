<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Transport et installation : montants ajoutés au total de la facture, SANS TVA.
 * Les factures existantes valent 0 (rien ne change pour elles).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('transport_amount', 12, 2)->default(0)->after('tax_amount');
            $table->string('transport_note', 255)->nullable()->after('transport_amount');
            $table->decimal('installation_amount', 12, 2)->default(0)->after('transport_note');
            $table->string('installation_note', 255)->nullable()->after('installation_amount');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['transport_amount', 'transport_note', 'installation_amount', 'installation_note']);
        });
    }
};
