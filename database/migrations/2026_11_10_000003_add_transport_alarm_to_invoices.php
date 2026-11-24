<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alarme de transport : date de rappel + transporteur.
 * L'alarme reste active jusqu'à ce qu'une personne l'ARRÊTE (jamais automatiquement).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->date('transport_remind_at')->nullable()->after('transport_note');
            $table->string('transport_carrier', 120)->nullable()->after('transport_remind_at');
            $table->string('transport_phone', 40)->nullable()->after('transport_carrier');
            $table->timestamp('transport_alarm_stopped_at')->nullable()->after('transport_phone');
            $table->foreignId('transport_alarm_stopped_by')->nullable()->after('transport_alarm_stopped_at')
                ->constrained('users')->nullOnDelete();

            $table->index('transport_remind_at');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('transport_alarm_stopped_by');
            $table->dropIndex(['transport_remind_at']);
            $table->dropColumn(['transport_remind_at', 'transport_carrier', 'transport_phone', 'transport_alarm_stopped_at']);
        });
    }
};
