<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
    Schema::create('activity_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->string('user_name')->nullable();          // لقطة: تبقى حتى لو تغيّر الاسم
    $table->string('action', 40);
    $table->string('subject_type')->nullable();
    $table->unsignedBigInteger('subject_id')->nullable();
    $table->string('subject_label')->nullable();
    $table->string('description', 500);
    $table->json('properties')->nullable();           // before / after / data
    $table->string('ip', 45)->nullable();
    $table->string('user_agent', 255)->nullable();
    $table->timestamp('created_at')->useCurrent();    // لا updated_at

    $table->index('created_at');
    $table->index(['subject_type', 'subject_id']);
    $table->index(['user_id', 'created_at']);
    $table->index('action');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
