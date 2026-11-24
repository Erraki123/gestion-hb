<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hnb_market_observations', function(Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('company', 180);
            $table->string('city', 100);
            $table->string('sku', 60);
            $table->string('product_name', 255);
            $table->string('unit', 20);
            $table->string('basis', 3);
            $table->decimal('public_price', 14, 2);
            $table->decimal('catalog_price', 14, 2);
            $table->string('source_url', 1000);
            $table->date('observed_on');
            $table->timestamps();
            $table->index(['observed_on','id']);
        });
    }
    public function down(): void { Schema::dropIfExists('hnb_market_observations'); }
};
