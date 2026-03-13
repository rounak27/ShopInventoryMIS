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
        Schema::create('purchases', function (Blueprint $table) {

            $table->id();

            $table->foreignId('supplier_id')->constrained();

            $table->foreignId('created_by')->constrained('users');

            $table->string('po_reference')->unique();

            $table->date('purchase_date');

            $table->decimal('total_cost',12,2)->default(0);

            $table->text('notes')->nullable();

            $table->enum('status',['draft','confirmed','received'])->default('draft');

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
