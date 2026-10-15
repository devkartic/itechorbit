<?php

namespace App\Services;

use App\Models\Admin\Link;
use App\Models\Admin\Permission;
use App\Models\Admin\Post;
use App\Models\Admin\Skill;
use App\Models\Admin\Tag;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Inertia\Inertia;

class AdminPresentation
{
    public function navigation(): array
    {
        $links = Link::with('module.menu')->get()->keyBy('url');
        $modules = [...config('admin'), 'permissions' => ['title' => 'Roles & permissions']];

        return collect($modules)->filter(fn ($module, $resource) => Permission::has_permission($links[$resource]->id ?? null, 'index'))
            ->map(function ($module, $resource) use ($links) {
                $link = $links[$resource] ?? null;
                $fallback = in_array($resource, ['posts', 'categories', 'tags', 'about-info', 'service-info', 'contact-info', 'privacy-policies']) ? 'Content' : (in_array($resource, ['menus', 'modules', 'links', 'roles', 'users', 'permissions']) ? 'Administration' : 'Portfolio & inquiries');

                return ['label' => $module['title'], 'url' => '/'.$resource, 'group' => $link?->module?->menu?->name ?? $fallback, 'module' => $link?->module?->name ?? 'Manage', 'order' => $link?->order_number,
                    '_ordering' => [$link?->module?->menu?->order_number, $link?->module?->order_number, $link?->order_number]];
            })->sort(function ($left, $right) {
                foreach (['group', 'module', 'label'] as $level => $name) {
                    $leftOrder = $left['_ordering'][$level];
                    $rightOrder = $right['_ordering'][$level];
                    // Explicit numbers (including zero) precede unset values at each level.
                    $comparison = (($leftOrder === null) <=> ($rightOrder === null))
                        ?: ((int) $leftOrder <=> (int) $rightOrder)
                        ?: strnatcasecmp($left[$name], $right[$name])
                        ?: strcmp($left[$name], $right[$name]);
                    if ($comparison !== 0) {
                        return $comparison;
                    }
                }

                return strcmp($left['url'], $right['url']);
            })->map(function ($item) {
                unset($item['_ordering']);

                return $item;
            })->values()->all();
    }

    private function abilities(string $resource): array
    {
        $link = Link::where('url', $resource)->first();
        $actions = collect(['index', 'create', 'edit', 'destroy'])->mapWithKeys(fn ($action) => [$action => Permission::has_permission($link?->id, $action)])->all();
        if (in_array($resource, ['contacts', 'users'])) {
            $actions['create'] = false;
        }
        if ($resource === 'basics') {
            $actions['destroy'] = false;
        }

        return $actions;
    }

    private function attributes(Model $model): array
    {
        // Only explicit form fields plus safe operational metadata; passwords/provider IDs/tokens never leave the server.
        $values = $model->only(array_diff([...$model->getFillable(), 'id', 'created_at', 'updated_at'], ['password', 'provider_id', 'remember_token']));
        unset($values['user_id'], $values['updated_by']);

        return $values;
    }

    public function render(string $resource, string $action, array $data = [])
    {
        $module = config('admin.'.$resource);
        abort_unless($module, 404);
        $table = app(AdminTable::class);
        $filters = $table->filters(request(), $resource);
        $model = new $module['model'];
        $record = collect($data)->first(fn ($item) => $item instanceof $module['model']);
        $fields = $module['fields'];
        if ($resource === 'users' && ! User::verify_default_admin()) {
            $fields = array_values(array_filter($fields, fn ($field) => ! in_array($field['name'], ['role_id', 'is_checked'])));
        }
        $records = $action === 'index' ? $table->records($resource, $filters) : null;
        $options = [];
        foreach (in_array($action, ['create', 'edit']) ? $fields : [] as $field) {
            if (empty($field['options'])) {
                continue;
            }
            $source = config('admin.'.$field['options']);
            $choices = $source['model']::query();
            if ($field['options'] === 'skills') {
                $choices->where('user_id', $record?->user_id ?? request()->user()->id);
            }
            if ($field['options'] === 'roles' && ! User::verify_default_admin()) {
                $choices->where('id', request()->user()->role_id);
            }
            $options[$field['name']] = $choices->orderBy('name')->get(['id', 'name'])->map(fn ($item) => ['value' => (string) $item->id, 'label' => $item->name])->all();
        }
        $values = $record ? $this->attributes($record) : [];
        if ($record) {
            foreach (['tags' => Tag::class, 'technology_used' => Skill::class] as $field => $type) {
                if (! empty($values[$field])) {
                    $names = json_decode($values[$field], true) ?: [];
                    $choices = $type::whereIn('name', $names);
                    if ($type === Skill::class) {
                        $choices->where('user_id', $record->user_id);
                    }
                    $values[$field] = $choices->pluck('id')->map(fn ($id) => (string) $id)->all();
                }
            }
            $values['link_url'] = $values['url'] ?? '';
            $values['is_checked'] = (bool) $record->status;
            $values['is_continuing'] = $resource === 'experiences' && ! $record->resign_date;
        }

        return Inertia::render('Admin/Resource', [
            'resource' => $resource, 'title' => $module['title'], 'action' => $action,
            'fields' => $fields, 'options' => $options, 'record' => $values,
            'previews' => $action === 'show' ? collect($fields)->where('type', 'richtext')->mapWithKeys(fn ($field) => [$field['name'] => app(PublicContent::class)->html($values[$field['name']] ?? '')])->all() : [],
            'records' => $records, 'filters' => $filters, 'columns' => $table->columns($resource),
            'abilities' => $this->abilities($resource), 'navigation' => $this->navigation(),
            'isAdmin' => User::verify_default_admin(),
        ]);
    }

    public function dashboard()
    {
        $admin = User::verify_default_admin();
        $navigation = $this->navigation();
        $paths = array_column($navigation, 'url');

        return Inertia::render('Admin/Dashboard', [
            'navigation' => $navigation,
            'canCreateArticle' => $this->abilities('posts')['create'],
            'recentArticles' => in_array('/posts', $paths) ? Post::when(! $admin, fn ($q) => $q->where('user_id', request()->user()->id))->latest('updated_at')->limit(5)->get(['id', 'title', 'status', 'updated_at']) : [],
            'stats' => [
                'Articles' => Post::when(! $admin, fn ($q) => $q->where('user_id', request()->user()->id))->count(),
                'Published articles' => Post::public_posts()->when(! $admin, fn ($q) => $q->where('user_id', request()->user()->id))->count(),
                'New inquiries' => Contact::where('user_id', request()->user()->id)->where('read_status', 0)->count(),
                'Projects' => request()->user()->projects()->count(),
            ],
        ]);
    }
}
