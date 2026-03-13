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
        Schema::create('stock_ledgers', function (Blueprint $table) {

            $table->id();

            $table->foreignId('variant_id')->constrained('item_variants');

            $table->foreignId('user_id')->constrained('users');

            $table->foreignId('purchase_item_id')
                ->nullable()
                ->constrained('purchase_items')
                ->nullOnDelete();

            $table->enum('action_type',[
                'purchase',
                'sale',
                'adjustment',
                'return',
                'damage'
            ]);

            $table->integer('quantity_change');

            $table->integer('stock_before');

            $table->integer('stock_after');

            $table->string('reference_no')->nullable();

            $table->text('notes')->nullable();

            $table->date('transaction_date');

            $table->timestamps();
            $table->index('variant_id');
            $table->index('transaction_date');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_ledgers');
    }
};
