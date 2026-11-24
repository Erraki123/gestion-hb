<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hnb_quote_learning_samples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->unique()->constrained('quotes')->cascadeOnDelete();
            $table->unsignedBigInteger('client_id'); // Grouping only, never a model feature.
            $table->decimal('amount_ttc', 12, 2);
            $table->unsignedSmallInteger('age_days');
            $table->dateTime('captured_at');
            $table->unsignedTinyInteger('outcome')->nullable();
            $table->dateTime('observed_at')->nullable();
            $table->index(['outcome', 'captured_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('hnb_quote_learning_samples'); }
};
