<?php

namespace Database\Seeders;

use App\Models\Admin\Route;
use Illuminate\Database\Seeder;

class RouteSeeder extends Seeder
{
    protected $routes = [
        ['name' => 'index'],
        ['name' => 'create'],
        ['name' => 'store'],
        ['name' => 'show'],
        ['name' => 'edit'],
        ['name' => 'update'],
        ['name' => 'destroy'],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->routes as $route) {
            Route::create($route);
        }
    }
}
