<?php

namespace Database\Seeders;

use App\Models\Admin\Link;
use Illuminate\Database\Seeder;

class LinkSeeder extends Seeder
{
    protected $links = [
        [
            'name' => 'Modules',
            'url' => 'modules',
            'module_id' => 1,
        ],
        [
            'name' => 'Links',
            'url' => 'links',
            'module_id' => 1,
        ],
        [
            'name' => 'Permissions',
            'url' => 'permissions',
            'module_id' => 1,
        ],
        [
            'name' => 'Roles',
            'url' => 'roles',
            'module_id' => 2,
        ],
        [
            'name' => 'Users',
            'url' => 'users',
            'module_id' => 2,
        ],
        [
            'name' => 'About',
            'url' => 'about-info',
            'module_id' => 3,
        ],
        [
            'name' => 'Services',
            'url' => 'service-info',
            'module_id' => 3,
        ],
        [
            'name' => 'Privacy & Policy',
            'url' => 'privacy-policies',
            'module_id' => 3,
        ],
        [
            'name' => 'Contact',
            'url' => 'contact-info',
            'module_id' => 3,
        ],
        [
            'name' => 'Categories',
            'url' => 'categories',
            'module_id' => 4,
        ],
        [
            'name' => 'Tags',
            'url' => 'tags',
            'module_id' => 4,
        ],
        [
            'name' => 'Posts',
            'url' => 'posts',
            'module_id' => 4,
        ],
        [
            'name' => 'Basic Information',
            'url' => 'basics',
            'module_id' => 5,
        ],
        [
            'name' => 'Social Links',
            'url' => 'social-links',
            'module_id' => 5,
        ],
        [
            'name' => 'Skills',
            'url' => 'skills',
            'module_id' => 5,
        ],
        [
            'name' => 'Services',
            'url' => 'services',
            'module_id' => 5,
        ],
        [
            'name' => 'Projects',
            'url' => 'projects',
            'module_id' => 5,
        ],
        [
            'name' => 'Experiences',
            'url' => 'experiences',
            'module_id' => 5,
        ],
        [
            'name' => 'Testimonials',
            'url' => 'testimonials',
            'module_id' => 5,
        ],
        [
            'name' => 'Inbox',
            'url' => 'contacts',
            'module_id' => 6,
        ],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->links as $link) {
            Link::create($link);
        }
    }
}
