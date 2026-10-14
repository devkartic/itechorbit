<?php

namespace Database\Seeders;

use App\Models\Admin\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    protected $menus = [
        ['name' => 'Security Section'],
        ['name' => 'Blog Section'],
        ['name' => 'Portfolio Section'],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->menus as $menu) {
            Menu::create($menu);
        }
    }
}
