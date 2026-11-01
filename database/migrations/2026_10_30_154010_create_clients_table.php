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
    Schema::create('clients', function (Blueprint $table) {
        $table->id();
        $table->foreignId('commercial_id')->constrained('users')->restrictOnDelete();
        $table->string('name');
        $table->string('company')->nullable();
        $table->string('phone')->nullable();
        $table->string('email')->nullable();
        $table->string('address')->nullable();
        $table->string('city')->nullable();
        $table->string('ice', 30)->nullable(); // identifiant fiscal / ICE
        $table->enum('status', ['prospect', 'actif', 'inactif'])->default('prospect');
        $table->text('comment')->nullable();
        $table->softDeletes();
        $table->timestamps();

        $table->index(['commercial_id', 'status']);
        $table->index('name');
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
