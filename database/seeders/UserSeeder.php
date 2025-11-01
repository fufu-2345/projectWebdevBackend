<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'email' => 'admin@gmail.com',
            'password' => bcrypt('admin'),
            'name' => 'admin',
            'role' => 'admin',
            'profilepic' => null,
            'address' => null,
            'phone' => null,
            'birthday' => null,
        ]);
    }
}
