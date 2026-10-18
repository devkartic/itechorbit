<?php

use App\Models\Admin\AboutInfo;
use App\Models\Admin\Basic;
use App\Models\Admin\Category;
use App\Models\Admin\ContactInfo;
use App\Models\Admin\Experience;
use App\Models\Admin\Link;
use App\Models\Admin\Menu;
use App\Models\Admin\Module;
use App\Models\Admin\Post;
use App\Models\Admin\PrivacyPolicy;
use App\Models\Admin\Project;
use App\Models\Admin\Role;
use App\Models\Admin\Service;
use App\Models\Admin\ServiceInfo;
use App\Models\Admin\Skill;
use App\Models\Admin\SocialLink;
use App\Models\Admin\Tag;
use App\Models\Admin\Testimonial;
use App\Models\Admin\WebsiteProduct;
use App\Models\Contact;
use App\Models\User;

// Presentation configuration only. Existing server validation, permissions and write actions are authoritative.
return [
    'posts' => [
        'model' => Post::class, 'title' => 'Articles', 'owner' => true,
        'fields' => [
            ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
            ['name' => 'category_id', 'label' => 'Category', 'type' => 'select', 'required' => true, 'options' => 'categories'],
            ['name' => 'tags', 'label' => 'Article tags', 'type' => 'multiselect', 'required' => false, 'options' => 'tags'],
            ['name' => 'body', 'label' => 'Body', 'type' => 'richtext', 'required' => true],
            ['name' => 'codebase', 'label' => 'Codebase', 'type' => 'textarea', 'required' => false],
            ['name' => 'uploaded_image', 'label' => 'Cover image', 'storage' => 'image_path', 'max_kb' => 2048, 'type' => 'file', 'required' => false, 'accept' => '.jpg,.jpeg,.png,.webp'],
            ['name' => 'is_private', 'label' => 'Private article', 'type' => 'checkbox', 'required' => false],
            ['name' => 'is_checked', 'label' => 'Published / active', 'type' => 'checkbox', 'required' => false],
        ],
    ],
    'categories' => [
        'model' => Category::class, 'title' => 'Categories', 'owner' => false,
        'fields' => [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
            ['name' => 'fa_icon', 'label' => 'Legacy icon HTML (optional)', 'type' => 'text', 'required' => false],
            ['name' => 'order_number', 'label' => 'Order number', 'type' => 'number', 'required' => false],
            ['name' => 'is_checked', 'label' => 'Published / active', 'type' => 'checkbox', 'required' => false],
        ],
    ],
    'tags' => [
        'model' => Tag::class, 'title' => 'Tags', 'owner' => false,
        'fields' => [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'order_number', 'label' => 'Order number', 'type' => 'number', 'required' => false],
            ['name' => 'is_checked', 'label' => 'Published / active', 'type' => 'checkbox', 'required' => false],
        ],
    ],
    'projects' => [
        'model' => Project::class, 'title' => 'Projects', 'owner' => true,
        'fields' => [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'client_name', 'label' => 'Client name', 'type' => 'text', 'required' => true],
            ['name' => 'skill_id', 'label' => 'Primary technology', 'type' => 'select', 'required' => true, 'options' => 'skills'],
            ['name' => 'played_role', 'label' => 'Played role', 'type' => 'text', 'required' => false],
            ['name' => 'project_category', 'label' => 'Project category', 'type' => 'text', 'required' => false],
            ['name' => 'jobs', 'label' => 'Engineering contributions', 'type' => 'richtext', 'required' => false],
            ['name' => 'technology_used', 'label' => 'Technologies used', 'type' => 'multiselect', 'required' => false, 'options' => 'skills'],
            ['name' => 'project_url', 'label' => 'Project URL', 'type' => 'url', 'required' => false],
            ['name' => 'order_number', 'label' => 'Order number', 'type' => 'number', 'required' => false],
            ['name' => 'about_project', 'label' => 'About project', 'type' => 'richtext', 'required' => true],
            ['name' => 'uploaded_image', 'label' => 'Project screenshot', 'storage' => 'image_path', 'max_kb' => 2048, 'type' => 'file', 'required' => false, 'accept' => '.jpg,.jpeg,.png,.webp'],
            ['name' => 'is_checked', 'label' => 'Published / active', 'type' => 'checkbox', 'required' => false],
        ],
    ],
    'users' => [
        'model' => User::class, 'title' => 'Users', 'owner' => false,
        'fields' => [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
            ['name' => 'role_id', 'label' => 'Role', 'type' => 'select', 'required' => false, 'options' => 'roles'],
            ['name' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => false],
            ['name' => 'password_confirmation', 'label' => 'Password confirmation', 'type' => 'password', 'required' => false],
            ['name' => 'is_checked', 'label' => 'Published / active', 'type' => 'checkbox', 'required' => false],
        ],
    ],
    'contacts' => [
        'model' => Contact::class, 'title' => 'Contact inquiries', 'owner' => true,
        'fields' => [
        ],
    ],
    'basics' => [
        'model' => Basic::class, 'title' => 'Profile information', 'owner' => true,
        'fields' => [
            ['name' => 'first_name', 'label' => 'First name', 'type' => 'text', 'required' => true],
            ['name' => 'last_name', 'label' => 'Last name', 'type' => 'text', 'required' => true],
            ['name' => 'designation', 'label' => 'Designation', 'type' => 'text', 'required' => true],
            ['name' => 'mobile', 'label' => 'Mobile', 'type' => 'text', 'required' => true],
            ['name' => 'service_offering_title', 'label' => 'Service offering title', 'type' => 'text', 'required' => true],
            ['name' => 'self_introduction', 'label' => 'Self introduction', 'type' => 'richtext', 'required' => true],
            ['name' => 'career_objective', 'label' => 'Career objective', 'type' => 'richtext', 'required' => true],
            ['name' => 'additional_information', 'label' => 'Additional information', 'type' => 'richtext', 'required' => true],
            ['name' => 'address', 'label' => 'Address', 'type' => 'text', 'required' => true],
            ['name' => 'gender', 'label' => 'Gender', 'type' => 'text', 'required' => true],
            ['name' => 'marital_status', 'label' => 'Marital status', 'type' => 'text', 'required' => true],
            ['name' => 'birth_date', 'label' => 'Birth date', 'type' => 'date', 'required' => true],
            ['name' => 'experience_year', 'label' => 'Experience year', 'type' => 'number', 'required' => true],
            ['name' => 'uploaded_banner_image', 'label' => 'Banner image', 'storage' => 'banner_image_path', 'max_kb' => 2048, 'type' => 'file', 'required' => false, 'accept' => '.jpg,.jpeg,.png,.webp'],
            ['name' => 'uploaded_profile_image', 'label' => 'Profile photo', 'storage' => 'profile_image_path', 'max_kb' => 2048, 'type' => 'file', 'required' => false, 'accept' => '.jpg,.jpeg,.png,.webp'],
            ['name' => 'uploaded_cv', 'label' => 'CV document', 'storage' => 'cv_path', 'max_kb' => 5000, 'type' => 'file', 'required' => false, 'accept' => '.pdf'],
            ['name' => 'cv_public_status', 'label' => 'Make CV publicly downloadable', 'type' => 'checkbox', 'required' => false],
            ['name' => 'is_checked', 'label' => 'Published / active', 'type' => 'checkbox', 'required' => false],
        ],
    ],
    'social-links' => [
        'model' => SocialLink::class, 'title' => 'Social links', 'owner' => true,
        'fields' => [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'social_url', 'label' => 'Profile URL', 'type' => 'url', 'required' => true],
            ['name' => 'fa_icon', 'label' => 'Legacy icon HTML (optional)', 'type' => 'text', 'required' => false],
            ['name' => 'order_number', 'label' => 'Order number', 'type' => 'number', 'required' => false],
            ['name' => 'is_checked', 'label' => 'Published / active', 'type' => 'checkbox', 'required' => false],
        ],
    ],
    'skills' => [
        'model' => Skill::class, 'title' => 'Skills', 'owner' => true,
        'fields' => [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'fa_icon', 'label' => 'Legacy icon HTML (optional)', 'type' => 'text', 'required' => false],
            ['name' => 'order_number', 'label' => 'Order number', 'type' => 'number', 'required' => false],
            ['name' => 'is_checked', 'label' => 'Published / active', 'type' => 'checkbox', 'required' => false],
        ],
    ],
    'services' => [
        'model' => Service::class, 'title' => 'Profile services', 'owner' => true,
        'fields' => [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'fa_icon', 'label' => 'Legacy icon HTML (optional)', 'type' => 'text', 'required' => false],
            ['name' => 'service_url', 'label' => 'Service URL', 'type' => 'url', 'required' => false],
            ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
            ['name' => 'about_service', 'label' => 'About service', 'type' => 'richtext', 'required' => true],
            ['name' => 'order_number', 'label' => 'Order number', 'type' => 'number', 'required' => false],
            ['name' => 'is_checked', 'label' => 'Published / active', 'type' => 'checkbox', 'required' => false],
        ],
    ],
    'experiences' => [
        'model' => Experience::class, 'title' => 'Experience', 'owner' => true,
        'fields' => [
            ['name' => 'designation', 'label' => 'Designation', 'type' => 'text', 'required' => true],
            ['name' => 'fa_icon', 'label' => 'Legacy icon HTML (optional)', 'type' => 'text', 'required' => false],
            ['name' => 'organization', 'label' => 'Organization', 'type' => 'text', 'required' => true],
            ['name' => 'responsibilities', 'label' => 'Responsibilities', 'type' => 'richtext', 'required' => false],
            ['name' => 'join_date', 'label' => 'Start date', 'type' => 'date', 'required' => true],
            ['name' => 'is_continuing', 'label' => 'Current position', 'type' => 'checkbox', 'required' => false],
            ['name' => 'resign_date', 'label' => 'End date', 'type' => 'date', 'required' => false],
            ['name' => 'descriptions', 'label' => 'Descriptions', 'type' => 'richtext', 'required' => false],
            ['name' => 'order_number', 'label' => 'Order number', 'type' => 'number', 'required' => false],
            ['name' => 'is_checked', 'label' => 'Published / active', 'type' => 'checkbox', 'required' => false],
        ],
    ],
    'testimonials' => [
        'model' => Testimonial::class, 'title' => 'Testimonials', 'owner' => true,
        'fields' => [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'designation', 'label' => 'Designation', 'type' => 'text', 'required' => true],
            ['name' => 'organization', 'label' => 'Organization', 'type' => 'text', 'required' => true],
            ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
            ['name' => 'mobile', 'label' => 'Mobile', 'type' => 'text', 'required' => true],
            ['name' => 'ratings', 'label' => 'Ratings', 'type' => 'number', 'required' => true],
            ['name' => 'comment', 'label' => 'Comment', 'type' => 'richtext', 'required' => true],
            ['name' => 'order_number', 'label' => 'Order number', 'type' => 'number', 'required' => false],
            ['name' => 'is_checked', 'label' => 'Published / active', 'type' => 'checkbox', 'required' => false],
        ],
    ],
    'about-info' => [
        'model' => AboutInfo::class, 'title' => 'About content', 'owner' => false,
        'fields' => [
            ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
            ['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'text', 'required' => true],
            ['name' => 'descriptions', 'label' => 'Descriptions', 'type' => 'richtext', 'required' => false],
            ['name' => 'is_checked', 'label' => 'Published / active', 'type' => 'checkbox', 'required' => false],
        ],
    ],
    'service-info' => [
        'model' => ServiceInfo::class, 'title' => 'Website services', 'owner' => false,
        'fields' => [
            ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
            ['name' => 'short_title', 'label' => 'Card title (optional)', 'type' => 'text', 'required' => false],
            ['name' => 'slug', 'label' => 'URL slug', 'type' => 'text', 'required' => true],
            ['name' => 'subtitle', 'label' => 'Summary', 'type' => 'textarea', 'required' => true],
            ['name' => 'descriptions', 'label' => 'Description', 'type' => 'richtext', 'required' => true],
            ['name' => 'display_order', 'label' => 'Display order', 'type' => 'number', 'required' => false],
            ['name' => 'problem', 'label' => 'The challenge', 'type' => 'textarea', 'required' => false],
            ['name' => 'solution', 'label' => 'The solution', 'type' => 'textarea', 'required' => false],
            ['name' => 'deliverables', 'label' => 'Deliverables (one per line)', 'type' => 'textarea', 'required' => false],
            ['name' => 'technologies', 'label' => 'Technologies (one per line)', 'type' => 'textarea', 'required' => false],
            ['name' => 'approach', 'label' => 'Approach (one step per line)', 'type' => 'textarea', 'required' => false],
            ['name' => 'use_cases', 'label' => 'Use cases (one Title | Description per line)', 'type' => 'textarea', 'required' => false],
            ['name' => 'icon', 'label' => 'Icon', 'type' => 'select', 'required' => false, 'choices' => array_map(fn ($icon) => ['value' => $icon, 'label' => ucfirst($icon)], ['code', 'layers', 'refresh', 'network', 'building', 'database', 'gauge', 'shield', 'sparkles'])],
            ['name' => 'uploaded_image', 'label' => 'Service image', 'storage' => 'image_path', 'max_kb' => 2048, 'type' => 'file', 'required' => false, 'accept' => '.jpg,.jpeg,.png,.webp'],
            ['name' => 'remove_image', 'label' => 'Remove existing image', 'type' => 'checkbox', 'required' => false],
            ['name' => 'highlight', 'label' => 'Featured service', 'type' => 'checkbox', 'required' => false],
            ['name' => 'is_checked', 'label' => 'Published / active', 'type' => 'checkbox', 'required' => false],
        ],
    ],
    'product-info' => [
        'model' => WebsiteProduct::class, 'title' => 'Website products', 'owner' => false,
        'fields' => [
            ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
            ['name' => 'short_title', 'label' => 'Card title (optional)', 'type' => 'text', 'required' => false],
            ['name' => 'slug', 'label' => 'URL slug', 'type' => 'text', 'required' => true],
            ['name' => 'subtitle', 'label' => 'Summary', 'type' => 'textarea', 'required' => true],
            ['name' => 'descriptions', 'label' => 'Description', 'type' => 'richtext', 'required' => true],
            ['name' => 'display_order', 'label' => 'Display order', 'type' => 'number', 'required' => false],
            ['name' => 'problem', 'label' => 'The challenge', 'type' => 'textarea', 'required' => false],
            ['name' => 'solution', 'label' => 'The solution', 'type' => 'textarea', 'required' => false],
            ['name' => 'deliverables', 'label' => 'Deliverables (one per line)', 'type' => 'textarea', 'required' => false],
            ['name' => 'technologies', 'label' => 'Technologies (one per line)', 'type' => 'textarea', 'required' => false],
            ['name' => 'approach', 'label' => 'Approach (one step per line)', 'type' => 'textarea', 'required' => false],
            ['name' => 'use_cases', 'label' => 'Use cases (one Title | Description per line)', 'type' => 'textarea', 'required' => false],
            ['name' => 'icon', 'label' => 'Icon', 'type' => 'select', 'required' => false, 'choices' => array_map(fn ($icon) => ['value' => $icon, 'label' => ucfirst($icon)], ['code', 'layers', 'refresh', 'network', 'building', 'database', 'gauge', 'shield', 'sparkles'])],
            ['name' => 'price', 'label' => 'Package price (optional)', 'type' => 'number', 'required' => false, 'step' => '0.01'],
            ['name' => 'currency', 'label' => 'Currency (3 uppercase letters, e.g. USD)', 'type' => 'text', 'required' => false],
            ['name' => 'delivery_time', 'label' => 'Estimated delivery time', 'type' => 'text', 'required' => false],
            ['name' => 'uploaded_image', 'label' => 'Product image', 'storage' => 'image_path', 'max_kb' => 2048, 'type' => 'file', 'required' => false, 'accept' => '.jpg,.jpeg,.png,.webp'],
            ['name' => 'remove_image', 'label' => 'Remove existing image', 'type' => 'checkbox', 'required' => false],
            ['name' => 'highlight', 'label' => 'Featured product', 'type' => 'checkbox', 'required' => false],
            ['name' => 'is_checked', 'label' => 'Published / active', 'type' => 'checkbox', 'required' => false],
        ],
    ],
    'contact-info' => [
        'model' => ContactInfo::class, 'title' => 'Contact content', 'owner' => false,
        'fields' => [
            ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
            ['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'text', 'required' => true],
            ['name' => 'descriptions', 'label' => 'Descriptions', 'type' => 'richtext', 'required' => false],
            ['name' => 'is_checked', 'label' => 'Published / active', 'type' => 'checkbox', 'required' => false],
        ],
    ],
    'privacy-policies' => [
        'model' => PrivacyPolicy::class, 'title' => 'Privacy policy', 'owner' => false,
        'fields' => [
            ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
            ['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'text', 'required' => true],
            ['name' => 'descriptions', 'label' => 'Descriptions', 'type' => 'richtext', 'required' => true],
            ['name' => 'is_checked', 'label' => 'Published / active', 'type' => 'checkbox', 'required' => false],
        ],
    ],
    'roles' => [
        'model' => Role::class, 'title' => 'Roles', 'owner' => false,
        'fields' => [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'order_number', 'label' => 'Order number', 'type' => 'number', 'required' => false],
            ['name' => 'is_checked', 'label' => 'Published / active', 'type' => 'checkbox', 'required' => false],
        ],
    ],
    'menus' => [
        'model' => Menu::class, 'title' => 'Navigation groups', 'owner' => false,
        'fields' => [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'fa_icon', 'label' => 'Legacy icon HTML (optional)', 'type' => 'text', 'required' => false],
            ['name' => 'order_number', 'label' => 'Order number', 'type' => 'number', 'required' => false],
            ['name' => 'is_checked', 'label' => 'Published / active', 'type' => 'checkbox', 'required' => false],
        ],
    ],
    'modules' => [
        'model' => Module::class, 'title' => 'Navigation modules', 'owner' => false,
        'fields' => [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'menu_id', 'label' => 'Menu', 'type' => 'select', 'required' => true, 'options' => 'menus'],
            ['name' => 'fa_icon', 'label' => 'Legacy icon HTML (optional)', 'type' => 'text', 'required' => false],
            ['name' => 'order_number', 'label' => 'Order number', 'type' => 'number', 'required' => false],
            ['name' => 'is_checked', 'label' => 'Published / active', 'type' => 'checkbox', 'required' => false],
        ],
    ],
    'links' => [
        'model' => Link::class, 'title' => 'Navigation links', 'owner' => false,
        'fields' => [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'link_url', 'label' => 'Resource path', 'type' => 'text', 'required' => true],
            ['name' => 'module_id', 'label' => 'Module', 'type' => 'select', 'required' => true, 'options' => 'modules'],
            ['name' => 'fa_icon', 'label' => 'Legacy icon HTML (optional)', 'type' => 'text', 'required' => false],
            ['name' => 'order_number', 'label' => 'Order number', 'type' => 'number', 'required' => false],
            ['name' => 'is_checked', 'label' => 'Published / active', 'type' => 'checkbox', 'required' => false],
        ],
    ],
];
