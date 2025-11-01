<?php

namespace Database\Seeders;

use App\Models\Promotion;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
    public function run()
    {
        Promotion::create([
            'discount' => 15,
        ]);
        Promotion::create([
            'discount' => 20,
        ]);
        Promotion::create([
            'discount' => 30,
        ]);
    }
}
