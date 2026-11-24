<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('atlas_contact_decisions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('discovered_prospect_id')->unique();
            $table->char('fingerprint', 64);
            $table->string('decision', 16);
            $table->unsignedBigInteger('reviewed_by');
            $table->timestamp('reviewed_at');
            $table->timestamp('updated_at')->nullable();
        });
    }
    public function down(): void { Schema::dropIfExists('atlas_contact_decisions'); }
};
