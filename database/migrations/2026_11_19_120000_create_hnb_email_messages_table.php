<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('hnb_email_messages',function(Blueprint $table){
            $table->id(); $table->uuid('request_key')->unique();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient',254); $table->string('subject',180); $table->text('body');
            $table->string('status',24)->index(); $table->string('reason',255)->nullable();
            $table->timestamp('decided_at')->nullable(); $table->timestamp('attempted_at')->nullable(); $table->timestamp('submitted_at')->nullable();
            $table->timestamps(); $table->index(['client_id','created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('hnb_email_messages'); }
};
