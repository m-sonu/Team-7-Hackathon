<?php

use App\Models\BillItem;
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
        Schema::create(BillItem::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bill_id');
            $table->string('name');
            $table->decimal('price', 8, 2)->nullable();
            $table->boolean('is_claimable')->default(false);
            $table->mediumText('rejection_reason')->nullable();
            $table->timestamps();

            $table->index('bill_id');
            $table->index('is_claimable');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(BillItem::TABLE_NAME);
    }
};
