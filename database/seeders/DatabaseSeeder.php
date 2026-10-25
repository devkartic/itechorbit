<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            MenuSeeder::class,
            ModuleSeeder::class,
            LinkSeeder::class,
            WorkspaceNavigationSeeder::class,
            RouteSeeder::class,
            CategorySeeder::class,
            PostSeeder::class,
            ITechOrbitAiProductSeeder::class,
            WebsiteGrowthServicesSeeder::class,
        ]);

    }
}
