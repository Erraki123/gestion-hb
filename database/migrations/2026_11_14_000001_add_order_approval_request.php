<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('approval_status', 20)->default('not_requested');
            $table->foreignId('approval_requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approval_requested_at')->nullable();
            $table->foreignId('approval_decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approval_decided_at')->nullable();
            $table->text('approval_comment')->nullable();
            $table->index(['approval_status', 'created_at']);
        });
    }
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['approval_requested_by']);
            $table->dropForeign(['approval_decided_by']);
            $table->dropIndex(['approval_status', 'created_at']);
            $table->dropColumn(['approval_status', 'approval_requested_by', 'approval_requested_at', 'approval_decided_by', 'approval_decided_at', 'approval_comment']);
        });
    }
};
