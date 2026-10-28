<?php

namespace App\Services\Admin;

use App\Models\Admin\Link;
use App\Models\Admin\Permission;
use App\Models\Admin\Post;
use App\Models\Admin\Skill;
use App\Models\Admin\Tag;
use App\Models\Admin\WebsiteTechnology;
use App\Models\Contact;
use App\Models\User;
use App\Services\Shared\MediaFiles;
use App\Services\Shared\PublicContent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminPresentation
{
    public function navigation(): array
    {
        return app(SidebarNavigation::class)->items();
    }

    private function abilities(string $resource): array
    {
        $link = Link::where('url', $resource)->first();
        $actions = collect(['index', 'create', 'edit', 'destroy'])->mapWithKeys(fn ($action) => [$action => Permission::has_permission($link?->id, $action)])->all();
        if (in_array($resource, ['contacts', 'users'])) {
            $actions['create'] = false;
        }
        if ($resource === 'public-pages') {
            $actions['create'] = false;
            $actions['destroy'] = false;
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

    private function attachments(string $resource, Model $record, array $fields): array
    {
        $attachments = [];
        foreach ($fields as $field) {
            $storage = $field['storage'] ?? null;
            if (! $storage || ! $record->{$storage}) {
                continue;
            }
            $path = MediaFiles::resolve($record->{$storage});
            $mime = $path ? mime_content_type($path) : null;
            $type = match ($mime) {
                'application/pdf' => 'pdf', 'image/jpeg', 'image/png', 'image/webp' => 'image', default => 'unavailable'
            };
            $attachments[$storage] = ['label' => $field['label'], 'name' => basename($record->{$storage}), 'type' => $type,
                'url' => $type !== 'unavailable' ? route('workspace.media', ['resource' => $resource, 'record' => $record->id, 'field' => $storage], false) : null];
        }

        return $attachments;
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
            if (isset($field['choices'])) {
                $options[$field['name']] = $field['choices'];
            }
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
        if ($resource === 'website-technologies') {
            if ($action === 'create') {
                $values['show_in_filters'] = true;
            }
            if ($record) {
                $options['merge_target'] = WebsiteTechnology::where('id', '!=', $record->id)->orderBy('name')->get()
                    ->map(fn ($technology) => ['value' => (string) $technology->id, 'label' => $technology->name.($technology->status ? '' : ' (inactive)')])->all();
                $values['previous_names_and_links'] = DB::table('website_technology_aliases')
                    ->where('website_technology_id', $record->id)->orderBy('type')->orderBy('value')->get()
                    ->map(fn ($alias) => $alias->type.': '.$alias->value)->all();
            }
        }
        if ($resource === 'service-info') {
            $selected = $record?->websiteTechnologies()->pluck('website_technologies.id')->all() ?? [];
            $options['technology_ids'] = WebsiteTechnology::where(fn ($query) => $query->where('status', true)->orWhereIn('id', $selected))
                ->orderBy('display_order')->orderBy('name')->get()->map(fn ($technology) => [
                    'value' => (string) $technology->id, 'label' => $technology->name.($technology->status ? '' : ' (inactive)'),
                    'disabled' => ! $technology->status,
                ])->all();
            $values['technology_ids'] = array_map('strval', $selected);
            if ($action === 'show' && $record) {
                $values['technology_ids'] = $record->websiteTechnologies()->orderBy('display_order')->pluck('name')->all();
            }
            unset($values['technologies']);
        }
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
            if (in_array($resource, ['service-info', 'product-info'])) {
                foreach (['deliverables', 'technologies', 'approach'] as $field) {
                    if ($resource === 'service-info' && $field === 'technologies') {
                        continue;
                    }
                    $values[$field] = implode("\n", $record->{$field} ?? []);
                }
                $values['use_cases'] = implode("\n", array_map(fn ($item) => $item['title'].' | '.$item['description'], $record->use_cases ?? []));
            }
            $values['link_url'] = $values['url'] ?? '';
            $values['is_checked'] = (bool) $record->status;
            $values['is_continuing'] = $resource === 'experiences' && ! $record->resign_date;
        }

        return Inertia::render('Admin/Resource', [
            'resource' => $resource, 'title' => $module['title'], 'action' => $action,
            'fields' => $fields, 'options' => $options, 'record' => $values,
            'attachments' => $record ? $this->attachments($resource, $record, $fields) : [],
            'previews' => $action === 'show' ? collect($fields)->where('type', 'richtext')->mapWithKeys(fn ($field) => [$field['name'] => app(PublicContent::class)->html($values[$field['name']] ?? '')])->all() : [],
            'records' => $records, 'filters' => $filters, 'columns' => $table->columns($resource),
            'filterUsers' => $action === 'index' ? $table->userOptions($resource) : [],
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
