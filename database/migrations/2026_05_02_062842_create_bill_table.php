<?php

use App\Models\Bill;
use App\Models\BillUploadBatch;
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
        Schema::create(Bill::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->integer('category_id')->nullable();
            $table->string('bill_no')->nullable();
            $table->string('vat_no')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->decimal('approve_amount', 15, 2)->nullable();
            $table->foreignId('bill_upload_batch_id')
                ->nullable()
                ->after('category_monthly_pivot_id')
                ->constrained(BillUploadBatch::TABLE_NAME)
                ->onDelete('set null');
            $table->string('status')->default('pending');
            $table->text('raw_text')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('category_id');
            $table->index('created_at');
            $table->index('bill_upload_batch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(Bill::TABLE_NAME);
    }
};
