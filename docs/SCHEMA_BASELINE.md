# Migration schema inventory

Source definitions only; live database unavailable. No migrations run against existing data.

## Website offerings split

Migration `2026_10_18_000004_separate_website_products.php` moves product records
from `website_services` to `website_products`. Both retain descriptive, publishing,
ordering and image columns. Neither final table has `service_type`; optional
`price`, `currency` and `delivery_time` belong only to `website_products`.
The portfolio `services` table is unchanged.

## Website content table names

Migration `2026_10_18_000003_rename_website_content_tables.php` renames
`contact_infos` to `website_contact` and `about_infos` to `website_about`,
preserving records and columns. The definitions below retain the original
migration history. The `ContactInfo` and `AboutInfo` models explicitly use the
new names; existing admin and public URLs remain unchanged. The separate
`contacts` inquiry table is unchanged. Rollback restores the original names.

## 2014_10_12_000000_create_roles_table.php

```php
Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->tinyInteger('status')->default(0);
            $table->smallInteger('order_number')->nullable();
            $table->timestamps();
        });
    }
```

## 2014_10_12_000000_create_users_table.php

```php
Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('role_id')->default(4)->constrained('roles')->onUpdate('cascade')->onDelete('cascade');
            $table->tinyInteger('status')->default(1);
            $table->string('portfolio_id')->nullable();
            $table->tinyInteger('portfolio_status')->default(0);
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('provider_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }
```

## 2014_10_12_100000_create_password_resets_table.php

```php
Schema::create('password_resets', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }
```

## 2019_08_19_000000_create_failed_jobs_table.php

```php
Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }
```

## 2019_12_14_000001_create_personal_access_tokens_table.php

```php
Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }
```

## 2022_07_01_185702_create_menus_table.php

```php
Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('fa_icon')->nullable();
            $table->integer('order_number')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });
    }
```

## 2022_07_01_185750_create_modules_table.php

```php
Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('fa_icon')->nullable();
            $table->foreignId('menu_id')->constrained('menus')->onUpdate('cascade')->onDelete('cascade');
            $table->integer('order_number')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });
    }
```

## 2022_07_01_185929_create_links_table.php

```php
Schema::create('links', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url')->nullable(false);
            $table->string('fa_icon')->nullable();
            $table->foreignId('module_id')->constrained('modules')->onUpdate('cascade')->onDelete('cascade');
            $table->integer('order_number')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });
    }
```

## 2022_07_01_190659_create_routes_table.php

```php
Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('fa_icon')->nullable();
            $table->integer('order_number')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });
    }
```

## 2022_07_01_204937_create_permissions_table.php

```php
Schema::create('permissions', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('link_id')->constrained('links')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('route_id')->constrained('routes')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
        });
    }
```

## 2022_07_16_173939_create_categories_table.php

```php
Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('title');
            $table->string('fa_icon')->nullable();
            $table->integer('order_number')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });
    }
```

## 2022_07_16_174332_create_posts_table.php

```php
Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->text('codebase')->nullable();
            $table->string('image_path')->nullable();
            $table->tinyInteger('is_private')->default(0);
            $table->tinyInteger('status')->default(0);
            $table->integer('view_times')->default(0);
            $table->foreignId('category_id')->constrained('categories')->onUpdate('cascade')->onDelete('cascade');
            $table->string('tags')->nullable();
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
        });
    }
```

## 2022_07_18_174332_create_tags_table.php

```php
Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('order_number')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });
    }
```

## 2022_07_23_184420_create_basics_table.php

```php
Schema::create('basics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('designation');
            $table->string('mobile');
            $table->string('address');
            $table->string('service_offering_title');
            $table->string('career_objective');
            $table->text('self_introduction')->nullable();
            $table->text('additional_information')->nullable();
            $table->string('banner_image_path')->nullable();
            $table->string('profile_image_path')->nullable();
            $table->string('cv_path')->nullable();
            $table->tinyInteger('cv_public_status')->default(0);
            $table->float('experience_year');
            $table->date('birth_date');
            $table->string('gender', 10);
            $table->string('marital_status', 10);
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });
    }
```

## 2022_07_23_190345_create_social_links_table.php

```php
Schema::create('social_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->string('name');
            $table->string('fa_icon')->nullable();
            $table->string('social_url')->nullable();
            $table->string('status')->default(1);
            $table->string('order_number')->nullable();
            $table->timestamps();
        });
    }
```

## 2022_07_23_190401_create_skills_table.php

```php
Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->string('name');
            $table->string('fa_icon')->nullable();
            $table->string('status')->default(1);
            $table->string('order_number')->nullable();
            $table->timestamps();
        });
    }
```

## 2022_07_23_190418_create_services_table.php

```php
Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->string('name');
            $table->string('fa_icon')->nullable();
            $table->string('title');
            $table->string('about_service');
            $table->string('service_url')->nullable();
            $table->string('status')->default(1);
            $table->string('order_number')->nullable();
            $table->timestamps();
        });
    }
```

## 2022_07_23_190436_create_projects_table.php

```php
Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->string('name');
            $table->string('project_category')->comment('Example: erp, ecommerce')->nullable();
            $table->string('client_name');
            $table->foreignId('skill_id')->comment('Created project based on technology/skill')->constrained('skills')->onUpdate('cascade')->onDelete('cascade');
            $table->string('jobs')->nullable();
            $table->text('technology_used')->nullable();
            $table->string('played_role')->nullable();
            $table->text('about_project');
            $table->string('project_url')->nullable();
            $table->string('image_path')->nullable();
            $table->string('status')->default(1);
            $table->string('order_number')->nullable();
            $table->timestamps();
        });
    }
```

## 2022_07_23_190452_create_experiences_table.php

```php
Schema::create('experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->string('fa_icon')->nullable();
            $table->string('designation');
            $table->string('organization');
            $table->string('responsibilities')->nullable();
            $table->text('descriptions')->nullable();
            $table->date('join_date')->nullable();
            $table->date('resign_date')->nullable();
            $table->string('status')->default(1);
            $table->string('order_number')->nullable();
            $table->timestamps();
        });
    }
```

## 2022_07_23_190515_create_testimonials_table.php

```php
Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->string('name');
            $table->string('designation');
            $table->string('organization');
            $table->string('mobile');
            $table->string('email');
            $table->text('comment');
            $table->integer('ratings')->nullable();
            $table->string('status')->default(1);
            $table->string('order_number')->nullable();
            $table->timestamps();
        });
    }
```

## 2022_08_26_145745_create_contacts_table.php

```php
Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->comment('Message to User')->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->string('name');
            $table->string('email');
            $table->string('subject');
            $table->text('message');
            $table->tinyInteger('read_status')->default(0);
            $table->tinyInteger('sent_mail_status')->default(0);
            $table->timestamps();
        });
    }
```

## 2022_08_26_145813_create_newsletters_table.php

```php
Schema::create('newsletters', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->timestamps();
        });
    }
```

## 2022_08_31_064427_create_comments_table.php

```php
Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->unsigned();
            $table->integer('parent_id')->unsigned()->nullable();
            $table->text('comment');
            $table->integer('commentable_id')->unsigned();
            $table->string('commentable_type');
            $table->timestamps();
        });
    }
```

## 2022_09_20_172207_create_about_infos_table.php

```php
Schema::create('about_infos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle');
            $table->text('descriptions');
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });
    }
```

## 2022_09_21_160833_create_service_infos_table.php

```php
Schema::create('service_infos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle');
            $table->text('descriptions');
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });
    }
```

## 2022_09_21_170107_create_contact_infos_table.php

```php
Schema::create('contact_infos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle');
            $table->text('descriptions');
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });
    }
```

## 2022_10_06_024229_create_privacy_policies_table.php

```php
Schema::create('privacy_policies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle');
            $table->text('descriptions');
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });
    }
```

## 2022_11_14_154355_create_jobs_table.php

```php
Schema::create('jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
    }
```
