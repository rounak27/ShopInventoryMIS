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
        Schema::create('item_variants', function (Blueprint $table) {

            $table->id();

            $table->foreignId('item_id')->constrained()->cascadeOnDelete();

            $table->string('size');

            $table->string('color');

            $table->integer('current_stock')->default(0);

            $table->integer('reorder_level')->default(0);

            $table->string('barcode')->unique();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_variants');
    }
};
