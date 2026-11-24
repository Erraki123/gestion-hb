<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hnb_direction_alert_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('alert_key', 90);
            $table->char('fingerprint', 64);
            $table->timestamp('seen_at');
            $table->timestamp('updated_at')->nullable();
            $table->unique(['user_id', 'alert_key'], 'hnb_dir_alert_read_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hnb_direction_alert_reads');
    }
};
