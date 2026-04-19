<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('item_variants', function (Blueprint $table) {
            $table->string('sku', 80)->nullable()->unique()->after('item_id');
            $table->decimal('selling_price', 10, 2)->default(0)->after('reorder_level');
            $table->string('image_path')->nullable()->after('selling_price');
        });

        // Backfill existing variants so each row has its own SKU and selling price.
        $rows = DB::table('item_variants as v')
            ->join('items as i', 'i.id', '=', 'v.item_id')
            ->select('v.id', 'i.sku as item_sku', 'i.selling_price as item_selling_price')
            ->orderBy('v.id')
            ->get();

        foreach ($rows as $row) {
            DB::table('item_variants')
                ->where('id', $row->id)
                ->update([
                    'sku' => strtoupper($row->item_sku) . '-D' . str_pad((string) $row->id, 4, '0', STR_PAD_LEFT),
                    'selling_price' => $row->item_selling_price,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_variants', function (Blueprint $table) {
            $table->dropUnique(['sku']);
            $table->dropColumn(['sku', 'selling_price', 'image_path']);
        });
    }
};
