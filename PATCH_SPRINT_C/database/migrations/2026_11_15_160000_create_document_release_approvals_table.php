<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('document_release_approvals', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 16);
            $table->unsignedBigInteger('document_id');
            $table->char('fingerprint', 64);
            $table->string('status', 16);
            $table->unsignedBigInteger('requested_by');
            $table->timestamp('requested_at');
            $table->unsignedBigInteger('decided_by')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->index(['document_type', 'document_id', 'id'], 'doc_release_lookup');
        });
    }
    public function down(): void { Schema::dropIfExists('document_release_approvals'); }
};
