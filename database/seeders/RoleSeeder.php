<?php

namespace Database\Seeders;

use App\Models\Admin\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    protected $roles = [
        ['name' => 'Super Admin'],
        ['name' => 'Admin'],
        ['name' => 'Auditor'],
        ['name' => 'User'],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->roles as $role) {
            Role::create($role);
        }
    }
}
