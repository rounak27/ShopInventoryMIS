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
            $table->foreignId('created_by')->constrained('users');

            $table->string('customer_name')->nullable();
            $table->string('customer_pan')->nullable();

            $table->string('bill_number')->unique();
            $table->string('fiscal_year');

            $table->decimal('sub_total', 12, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('taxable_amount', 12, 2);
            $table->decimal('vat', 10, 2)->default(0);
            $table->decimal('grand_total', 12, 2);

            $table->enum('payment_method', ['cash', 'card', 'fonepay', 'esewa']);
            $table->enum('status', ['completed', 'returned', 'cancelled'])->default('completed');

            $table->date('sale_date');
            $table->dateTime('printed_at')->nullable();
            $table->timestamps();

            $table->index(['fiscal_year', 'bill_number']);
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