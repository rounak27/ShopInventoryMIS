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

            $table->id();

            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();

            $table->foreignId('variant_id')->constrained('item_variants');

            $table->integer('quantity');

            $table->decimal('price_per_unit',10,2);

            $table->decimal('total_price',12,2);
            $table->index(['sale_id','variant_id']);
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
