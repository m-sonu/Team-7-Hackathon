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
        Schema::table('bill', function (Blueprint $table) {
            $table->foreignId('category_monthly_pivot_id')->nullable()->after('category_id')->constrained('category_monthly_pivot')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bill', function (Blueprint $table) {
            $table->dropForeign(['category_monthly_pivot_id']);
            $table->dropColumn('category_monthly_pivot_id');
        });
    }
};
