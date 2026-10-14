<?php

namespace Database\Seeders;

use App\Models\Admin\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    protected $categories = [
        [
            'name' => 'Laravel',
            'title' => 'title example',
        ],
        [
            'name' => 'ReactJs',
            'title' => 'title example',
        ],
        [
            'name' => 'PHP',
            'title' => 'title example',
        ],
        [
            'name' => 'Javascript',
            'title' => 'title example',
        ],
        [
            'name' => 'Ecmascript',
            'title' => 'title example',
        ],
        [
            'name' => 'Oracle',
            'title' => 'title example',
        ],
        [
            'name' => 'HTML & CSS3',
            'title' => 'title example',
        ],
        [
            'name' => 'Mysql',
            'title' => 'title example',
        ],
        [
            'name' => 'Software Architecture',
            'title' => 'title example',
        ],
        [
            'name' => 'VueJs',
            'title' => 'title example',
        ],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->categories as $category) {
            Category::create($category);
        }
    }
}
