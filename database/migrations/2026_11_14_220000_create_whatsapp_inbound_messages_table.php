<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('whatsapp_inbound_messages', function (Blueprint $table) {
            $table->id();
            $table->string('provider_message_id', 200)->unique();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('sender', 25);
            $table->text('body');
            $table->timestamp('received_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['read_at', 'created_at']);
            $table->index('sender');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_inbound_messages');
    }
};
