<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->boolean('requires_approval')->default(false);
            $table->string('approval_status', 20)->nullable();
            $table->foreignId('approval_requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approval_requested_at')->nullable();
            $table->foreignId('approval_decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approval_decided_at')->nullable();
            $table->text('approval_comment')->nullable();
        });
        // Existing quotes with discounts or manual lines are flagged to avoid automatic sending.
        DB::table('quotes')->whereIn('id', DB::table('quote_items')
            ->select('quote_id')->where(function ($q) {
                $q->where('discount_percent', '>', 0)->orWhereNull('product_id');
            }))->update(['requires_approval' => true, 'approval_status' => 'pending']);
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approval_requested_by');
            $table->dropConstrainedForeignId('approval_decided_by');
            $table->dropColumn(['requires_approval', 'approval_status', 'approval_requested_at',
                'approval_decided_at', 'approval_comment']);
        });
    }
};
