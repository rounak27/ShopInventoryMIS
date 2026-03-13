<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\ItemVariant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [

            [
                'category_id'   => 1,
                'name'          => 'Oxford Button Shirt',
                'sku'           => 'CLT-001',
                'brand'         => 'Arrow',
                'cost_price'    => 1200,
                'selling_price' => 2499,
                'description'   => 'Classic cotton oxford shirt',
                'image_path'    => null,
                'is_active'     => true,
            ],

            [
                'category_id'   => 1,
                'name'          => 'Slim Chino Trouser',
                'sku'           => 'CLT-002',
                'brand'         => "Levi's",
                'cost_price'    => 900,
                'selling_price' => 1899,
                'description'   => 'Slim fit stretch chinos',
                'image_path'    => null,
                'is_active'     => true,
            ],

            [
                'category_id'   => 2,
                'name'          => 'Floral Midi Dress',
                'sku'           => 'CLT-003',
                'brand'         => 'Zara',
                'cost_price'    => 1500,
                'selling_price' => 3299,
                'description'   => 'Boho floral print midi',
                'image_path'    => null,
                'is_active'     => true,
            ],

            [
                'category_id'   => 1,
                'name'          => 'Denim Jacket',
                'sku'           => 'CLT-004',
                'brand'         => 'Wrangler',
                'cost_price'    => 2000,
                'selling_price' => 4199,
                'description'   => 'Classic blue denim jacket',
                'image_path'    => null,
                'is_active'     => true,
            ],

            [
                'category_id'   => 2,
                'name'          => 'Knit Pullover',
                'sku'           => 'CLT-005',
                'brand'         => 'H&M',
                'cost_price'    => 1300,
                'selling_price' => 2799,
                'description'   => 'Soft ribbed knit pullover',
                'image_path'    => null,
                'is_active'     => true,
            ],

        ];

        foreach ($items as $item) {
            Item::create($item);
        }
    }


}
