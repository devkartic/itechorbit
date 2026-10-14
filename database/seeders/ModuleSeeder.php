<?php

namespace Database\Seeders;

use App\Models\Admin\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    protected $modules = [
        [
            'name' => 'Security Control',
            'fa_icon' => '<i class="fa-solid fa-lock"></i>',
            'menu_id' => 1,
        ],
        [
            'name' => 'Users Control',
            'fa_icon' => '<i class="fa-solid fa-users"></i>',
            'menu_id' => 1,
        ],
        [
            'name' => 'Site Control',
            'fa_icon' => '<i class="fa-solid fa-circle-info"></i>',
            'menu_id' => 2,
        ],
        [
            'name' => 'Post Control',
            'fa_icon' => '<i class="fa-solid fa-blog"></i>',
            'menu_id' => 2,
        ],
        [
            'name' => 'Portfolio Control',
            'fa_icon' => '<i class="fa-solid fa-circle-info"></i>',
            'menu_id' => 3,
        ],
        [
            'name' => 'Contact Mail',
            'fa_icon' => '<i class="fa-solid fa-envelope"></i>',
            'menu_id' => 3,
        ],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->modules as $module) {
            Module::create($module);
        }
    }
}
