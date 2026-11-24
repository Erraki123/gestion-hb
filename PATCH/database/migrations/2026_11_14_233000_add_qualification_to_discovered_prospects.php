<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('discovered_prospects', function (Blueprint $table) {
            $table->unsignedTinyInteger('qualification_score')->nullable();
            $table->string('qualification_reason', 255)->nullable();
            $table->string('qualification_method', 15)->nullable();
            $table->timestamp('qualified_at')->nullable();
            $table->index(['status','qualification_score']);
        });
    }
    public function down(): void {
        Schema::table('discovered_prospects', function (Blueprint $table) {
            $table->dropIndex(['status','qualification_score']);
            $table->dropColumn(['qualification_score','qualification_reason','qualification_method','qualified_at']);
        });
    }
};
