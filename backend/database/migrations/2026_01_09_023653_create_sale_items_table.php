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
        Schema::create('sale_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('product_id')->constrained()->restrictOnDelete();
            $table->string('product_name');
            $table->decimal('price', 15, 2);
            $table->integer('quantity');
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
