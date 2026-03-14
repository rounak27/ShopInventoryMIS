<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ItemVariantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('item_variants')->insert([

            // Oxford Shirt
            [
                'item_id' => 3,
                'size' => 'M',
                'color' => 'White',
                'current_stock' => 20,
                'reorder_level' => 5,
                'barcode' => 'OXF-M-WHT-001',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'item_id' => 3,
                'size' => 'L',
                'color' => 'Blue',
                'current_stock' => 15,
                'reorder_level' => 5,
                'barcode' => 'OXF-L-BLU-002',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Slim Chino Trouser
            [
                'item_id' => 4,
                'size' => '32',
                'color' => 'Khaki',
                'current_stock' => 18,
                'reorder_level' => 6,
                'barcode' => 'CHN-32-KHK-003',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'item_id' => 4,
                'size' => '34',
                'color' => 'Black',
                'current_stock' => 12,
                'reorder_level' => 6,
                'barcode' => 'CHN-34-BLK-004',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Floral Midi Dress
            [
                'item_id' => 5,
                'size' => 'S',
                'color' => 'Red',
                'current_stock' => 10,
                'reorder_level' => 4,
                'barcode' => 'DRS-S-RED-005',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'item_id' => 5,
                'size' => 'M',
                'color' => 'Pink',
                'current_stock' => 14,
                'reorder_level' => 4,
                'barcode' => 'DRS-M-PNK-006',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Denim Jacket
            [
                'item_id' => 6,
                'size' => 'M',
                'color' => 'Blue',
                'current_stock' => 8,
                'reorder_level' => 3,
                'barcode' => 'DNM-M-BLU-007',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'item_id' => 6,
                'size' => 'L',
                'color' => 'Blue',
                'current_stock' => 6,
                'reorder_level' => 3,
                'barcode' => 'DNM-L-BLU-008',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Knit Pullover
            [
                'item_id' => 7,
                'size' => 'M',
                'color' => 'Grey',
                'current_stock' => 11,
                'reorder_level' => 4,
                'barcode' => 'KNT-M-GRY-009',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'item_id' => 7,
                'size' => 'L',
                'color' => 'Black',
                'current_stock' => 9,
                'reorder_level' => 4,
                'barcode' => 'KNT-L-BLK-010',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);
    }


}
