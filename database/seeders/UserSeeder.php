<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::factory()->create([
            'name' => 'Example Owner',
            'role_id' => 1,
            'status' => 1,
            'email' => 'owner@example.com',
        ]);

        User::factory(999)->create();
    }
}
