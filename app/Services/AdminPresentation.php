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
        $links = Link::pluck('id', 'url');

        return collect(config('admin'))->filter(fn ($module, $resource) => Permission::has_permission($links[$resource] ?? null, 'index'))
            ->map(fn ($module, $resource) => ['label' => $module['title'], 'url' => '/'.$resource])->values()->all();
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
        $filters = request()->validate(['key' => ['nullable', 'string', 'max:200'], 'page' => ['nullable', 'integer', 'min:1']]);
        $model = new $module['model'];
        $record = collect($data)->first(fn ($item) => $item instanceof $module['model']);
        $fields = $module['fields'];
        if ($resource === 'users' && ! User::verify_default_admin()) {
            $fields = array_values(array_filter($fields, fn ($field) => ! in_array($field['name'], ['role_id', 'is_checked'])));
        }
        $query = $model->newQuery();
        if ($module['owner'] && ! User::verify_default_admin()) {
            $query->where('user_id', request()->user()->id);
        }
        if ($resource === 'users' && ! User::verify_default_admin()) {
            $query->where('id', request()->user()->id);
        }
        if (! empty($filters['key'])) {
            $search = array_intersect($model->getFillable(), ['name', 'title', 'subject', 'email', 'designation', 'organization']);
            $query->where(function ($q) use ($search, $filters) {
                foreach ($search as $field) {
                    $q->orWhere($field, 'like', '%'.$filters['key'].'%');
                }
            });
        }
        $records = $action === 'index' ? $query->latest('id')->paginate(20)->withQueryString()->through(fn ($item) => $this->attributes($item)) : null;
        $options = [];
        foreach ($fields as $field) {
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
            'records' => $records, 'filters' => ['key' => $filters['key'] ?? ''],
            'abilities' => $this->abilities($resource), 'navigation' => $this->navigation(),
            'isAdmin' => User::verify_default_admin(),
        ]);
    }

    public function dashboard()
    {
        $admin = User::verify_default_admin();

        return Inertia::render('Admin/Dashboard', [
            'navigation' => $this->navigation(),
            'stats' => [
                'Articles' => Post::when(! $admin, fn ($q) => $q->where('user_id', request()->user()->id))->count(),
                'Published articles' => Post::public_posts()->when(! $admin, fn ($q) => $q->where('user_id', request()->user()->id))->count(),
                'New inquiries' => Contact::where('user_id', request()->user()->id)->where('read_status', 0)->count(),
                'Projects' => request()->user()->projects()->count(),
            ],
        ]);
    }
}
