<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hnb_whatsapp_outbound_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_key')->unique();
            $table->foreignId('inbound_id')->nullable()->constrained('whatsapp_inbound_messages')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient', 25);
            $table->text('body');
            $table->string('status', 24)->index();
            $table->string('provider_message_id', 200)->nullable();
            $table->string('reason', 255)->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamps();
            $table->index(['client_id','recipient','created_at'], 'hnb_wa_conversation_idx');
        });
    }
    public function down(): void { Schema::dropIfExists('hnb_whatsapp_outbound_messages'); }
};
