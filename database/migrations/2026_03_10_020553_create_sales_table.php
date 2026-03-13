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

            $table->id();

            $table->string('invoice_no')->unique();

            $table->foreignId('created_by')->constrained('users');

            $table->decimal('total_amount',12,2);

            $table->decimal('discount',10,2)->default(0);

            $table->decimal('tax',10,2)->default(0);

            $table->string('payment_method');

            $table->dateTime('sale_date');

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
