<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('prospect_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->unique()->constrained('clients')->cascadeOnDelete();
            $table->string('category', 30)->default('client_final');
            $table->string('stage', 30)->default('nouveau');
            $table->string('source', 120)->nullable();
            $table->dateTime('next_follow_up_at')->nullable();
            $table->boolean('do_not_contact')->default(false);
            $table->timestamps();
            $table->index(['stage', 'next_follow_up_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('prospect_details'); }
};
