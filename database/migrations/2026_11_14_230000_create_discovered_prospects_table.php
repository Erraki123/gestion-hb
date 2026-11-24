<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('discovered_prospects', function (Blueprint $table) {
            $table->id();
            $table->string('source', 50);
            $table->string('source_id', 180);
            $table->string('company', 255);
            $table->string('city', 100)->nullable();
            $table->string('category', 30)->default('installateur');
            $table->string('phone', 50)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('website', 500)->nullable();
            $table->string('source_url', 700)->nullable();
            $table->unsignedTinyInteger('score')->default(0);
            $table->string('status', 20)->default('pending'); // pending, accepted, dismissed
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->timestamp('discovered_at')->nullable();
            $table->timestamps();
            $table->unique(['source', 'source_id']);
            $table->index(['status', 'score']);
        });
    }
    public function down(): void { Schema::dropIfExists('discovered_prospects'); }
};
