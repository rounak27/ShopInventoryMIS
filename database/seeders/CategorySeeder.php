<?php

namespace Database\Seeders;

use Carbon\Carbon;
use DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('categories')->insert([
            [
                'id' => 1,
                'name' => "Men's Wear",
                'description' => 'Shirts, trousers, formal & casual',
                'created_at' => Carbon::parse('2026-01-10'),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => "Women's Wear",
                'description' => 'Dresses, tops, ethnic & casual',
                'created_at' => Carbon::parse('2026-01-10'),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => "Kids",
                'description' => 'Tees, frocks, boys & girls',
                'created_at' => Carbon::parse('2026-01-12'),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'name' => "Ethnic",
                'description' => 'Sarees, kurtas, ethnic wear',
                'created_at' => Carbon::parse('2026-01-15'),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'name' => "Accessories",
                'description' => 'Belts, scarves, bags, caps',
                'created_at' => Carbon::parse('2026-01-20'),
                'updated_at' => now(),
            ],
            [
                'id' => 6,
                'name' => "Footwear",
                'description' => 'Sneakers, sandals, formal shoes',
                'created_at' => Carbon::parse('2026-02-01'),
                'updated_at' => now(),
            ],
        ]);
    }

}
