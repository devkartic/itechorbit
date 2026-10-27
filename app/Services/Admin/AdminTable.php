<?php

namespace App\Services\Admin;

use App\Http\Controllers\Admin\PermissionController;
use App\Models\Admin\Link;
use App\Models\Admin\Permission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminTable
{
    public function columns(string $resource): array
    {
        $fields = config('admin.'.$resource.'.fields', []);
        $columns = match ($resource) {
            'public-pages' => ['page_key', 'heading', 'status'],
            'service-info', 'product-info' => ['title', 'display_order', 'status'],
            'website-technologies' => ['name', 'slug', 'services_count', 'display_order', 'show_on_homepage', 'show_in_filters', 'status'],
            'contacts' => ['name', 'email', 'subject', 'read_status'],
            'users' => ['name', 'email', 'status', 'portfolio_status'],
            default => [...collect($fields)->filter(fn ($field) => in_array($field['type'], ['text', 'email', 'date']) && $field['name'] !== 'fa_icon')->take(3)->pluck('name')->all(), 'status'],
        };

        return [...array_unique(array_map(fn ($column) => $column === 'link_url' ? 'url' : $column, $columns)), 'created_at'];
    }

    public function filters(Request $request, string $resource): array
    {
        $validated = $request->validate([
            'key' => ['nullable', 'string', 'max:200'],
            'page' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'per_page' => ['nullable', 'integer', Rule::in([10, 20, 25, 50, 100])],
            'sort' => ['nullable', Rule::in([...$this->columns($resource), 'id'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'status' => ['nullable', Rule::in(['', '0', '1'])],
        ]);
        $filters = array_replace(['key' => '', 'page' => 1, 'per_page' => 20, 'sort' => 'id', 'direction' => 'desc', 'status' => ''], array_filter($validated, fn ($value) => $value !== null));
        $filters['page'] = (int) $filters['page'];
        $filters['per_page'] = (int) $filters['per_page'];

        return $filters;
    }

    public function query(string $resource): Builder
    {
        $module = config('admin.'.$resource);
        abort_unless($module, 404);
        $query = $module['model']::query();
        if ($module['owner'] && ! User::verify_default_admin()) {
            $query->where('user_id', request()->user()->id);
        }
        if ($resource === 'users' && ! User::can_view_all_user_portfolios()) {
            $query->where('id', request()->user()->id);
        }

        return $query;
    }

    public function records(string $resource, array $filters)
    {
        $query = $this->query($resource);
        if (filled($filters['key'] ?? null)) {
            $search = array_intersect($query->getModel()->getFillable(), ['name', 'title', 'subject', 'email', 'designation', 'organization', 'first_name', 'last_name', 'heading', 'page_key']);
            $query->where(function ($q) use ($search, $filters) {
                foreach ($search as $field) {
                    $q->orWhere($field, 'like', '%'.$filters['key'].'%');
                }
            });
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where($resource === 'contacts' ? 'read_status' : 'status', $filters['status']);
        }
        $sort = $filters['sort'] ?: 'id';
        $query->orderBy($sort, $filters['direction'] ?: 'desc');
        if ($sort !== 'id') {
            $query->orderBy('id', 'desc');
        }
        $portfolios = $resource === 'users' && User::can_view_all_user_portfolios();
        $columns = array_values(array_unique(['id', ...$this->columns($resource), ...($resource === 'posts' ? ['is_private'] : [])]));
        $selected = [...array_diff($columns, ['services_count']), ...($portfolios ? ['portfolio_id'] : [])];

        // List responses never include rich bodies, passwords, provider IDs or tokens.
        $query->select($selected);
        if ($resource === 'website-technologies') {
            $query->withCount('services');
        }

        return $query->paginate($filters['per_page'] ?: 20)->withQueryString()->through(function ($item) use ($columns, $portfolios) {
            $row = $item->only($columns);
            if ($portfolios) {
                $row['public_portfolio'] = filled($item->portfolio_id)
                    ? '/portfolios/'.rawurlencode($item->portfolio_id) : null;
            }

            return $row;
        });
    }

    public function response(Request $request, string $resource)
    {
        abort_unless(config('admin.'.$resource), 404);
        PermissionController::initial_verification($request->user());
        abort_unless(Permission::has_permission(Link::where('url', $resource)->value('id'), 'index'), 403);
        $filters = $this->filters($request, $resource);

        return response()->json(['records' => $this->records($resource, $filters), 'filters' => $filters]);
    }
}
