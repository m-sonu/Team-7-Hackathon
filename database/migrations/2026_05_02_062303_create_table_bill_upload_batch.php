<?php

use App\Enums\AiProcessStatus;
use App\Models\BillUploadBatch;
use App\Models\Category;
use App\Models\CategoryMonthlyPivot;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(BillUploadBatch::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('currency', 10)->default('YEN');
            $table->enum('ai_processing', AiProcessStatus::all())
                ->default(AiProcessStatus::PROCESSING->value);
            // Link to the user
            $table->foreignId('user_id')->constrained(User::TABLE_NAME)->onDelete('cascade');

            // Link to the Category
            $table->foreignId('category_id')->constrained(Category::TABLE_NAME)->onDelete('cascade');

            $table->foreignId('category_monthly_pivot_id')
                ->nullable()
                ->constrained(CategoryMonthlyPivot::TABLE_NAME)
                ->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(BillUploadBatch::TABLE_NAME);
    }
};
