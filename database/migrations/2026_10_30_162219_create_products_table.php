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
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
    $table->string('name');
    $table->string('sku', 60)->unique();
    $table->text('description')->nullable();
    $table->string('unit', 20)->default('pièce');
    $table->decimal('purchase_price', 12, 2)->default(0);
    $table->decimal('sale_price', 12, 2)->default(0);
    $table->decimal('stock_quantity', 12, 2)->default(0);
    $table->decimal('min_stock', 12, 2)->default(0);
    $table->string('image_path')->nullable();
    $table->boolean('is_active')->default(true);
    $table->softDeletes();
    $table->timestamps();

    $table->index('name');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
