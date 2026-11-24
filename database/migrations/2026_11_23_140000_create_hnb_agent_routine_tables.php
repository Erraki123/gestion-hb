<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hnb_agent_routine_snoozes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('task_key', 90);
            $table->char('fingerprint', 64);
            $table->timestamp('snoozed_until');
            $table->timestamps();
            $table->unique(['user_id', 'task_key'], 'hnb_routine_snooze_unique');
        });
        Schema::create('hnb_agent_routine_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('run_date');
            $table->unsignedInteger('total_tasks')->default(0);
            $table->unsignedInteger('priority_tasks')->default(0);
            $table->timestamp('scanned_at');
            $table->unique(['user_id', 'run_date'], 'hnb_routine_run_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hnb_agent_routine_runs');
        Schema::dropIfExists('hnb_agent_routine_snoozes');
    }
};
