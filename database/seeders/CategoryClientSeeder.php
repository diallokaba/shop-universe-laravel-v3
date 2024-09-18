<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoryClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = ['Gold', 'Silver', 'Bronze'];

        foreach ($categories as $category) {
            DB::table('category_clients')->insert([
                'libelle' => $category,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
