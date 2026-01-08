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
        Schema::create('sales', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('transaction_number')->unique();
            $table->foreignUlid('store_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('cashier_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('total_amount', 15, 2);
            $table->decimal('paid_amount', 15, 2);
            $table->decimal('change_amount', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
