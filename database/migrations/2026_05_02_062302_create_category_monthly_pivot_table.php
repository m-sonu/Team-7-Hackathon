<?php

use App\Models\CategoryMonthlyPivot;
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
        Schema::create(CategoryMonthlyPivot::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('category')->onDelete('cascade');
            $table->string('month_year')->comment('e.g., 2026-05');
            $table->integer('bill_count')->default(0)->comment('Running count');
            $table->decimal('total_spent', 15, 2)->default(0.00)->comment('Running total');
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'category_id', 'month_year'], 'idx_user_category_month');
            $table->index('month_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(CategoryMonthlyPivot::TABLE_NAME);
    }
};
