<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_logs', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 20);            // email | whatsapp
            $table->string('kind', 30);               // quote | invoice | cheque_digest
            $table->string('recipient')->nullable();  // e-mail ou numéro
            $table->string('subject')->nullable();
            $table->string('document_type', 20)->nullable();
            $table->unsignedBigInteger('document_id')->nullable();
            $table->string('status', 20)->default('queued'); // queued | sent | failed | skipped
            $table->text('error')->nullable();
            $table->string('provider_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['document_type', 'document_id']);
            $table->index(['kind', 'recipient', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_logs');
    }
};
