<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\SendAccountStatusNotification;
use App\Models\Admin\Role;
use App\Models\User;
use App\Services\Admin\AdminPresentation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            PermissionController::permission_verify();

            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        return app(AdminPresentation::class)->render('users', 'index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show(User $user)
    {
        User::verify_ownership($user->id);

        return app(AdminPresentation::class)->render('users', 'show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit(User $user)
    {
        User::verify_ownership($user->id);

        if (User::verify_default_admin()) {
            $roles = Role::all();
        } else {
            $roles = Role::where('id', Auth::user()->role_id)->get();
        }

        return app(AdminPresentation::class)->render('users', 'edit', compact('user', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, User $user)
    {
        User::verify_ownership($user->id);

        $this->update_validator($request, $user);

        $user->name = $request->input('name');
        if (User::verify_default_admin() && $user->id !== Auth::id()) {
            $user->role_id = $request->input('role_id', $user->role_id);
        }
        if ($user->email !== $request->input('email')) {
            $user->email_verified_at = null;
        }
        $user->email = $request->input('email');
        if ($request->filled('password')) {
            $user->password = bcrypt($request->input('password'));
        }
        if (User::verify_default_admin() && $user->id !== Auth::id()) {
            $user->status = (int) filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN);
            $user->pending_email_activation = false;
        }
        $user->save();
        $this->notifyStatusChange($user, 'status');

        return response()->json(['success' => 'User updated successfully']);
    }

    private function update_validator($request, $user)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', "unique:users,email, $user->id"],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy(User $user)
    {
        User::verify_ownership($user->id);

        abort_if($user->id === Auth::id() || (int) $user->role_id === Role::$default_admin, 403, 'Your own account and super-admin accounts cannot be deleted here.');
        $user->delete();

        return response()->json(['success' => 'User deleted successfully']);
    }

    public function user_handler(Request $request): object
    {
        if (! User::verify_default_admin()) {
            abort(403, 'Access Forbidden');
        }

        $request->validate(['user_id' => ['required', 'integer', 'exists:users,id'], 'is_enable' => ['required', 'boolean']]);
        $user = User::findOrFail($request->input('user_id'));

        abort_if($user->id === Auth::id() && ! filter_var($request->input('is_enable'), FILTER_VALIDATE_BOOLEAN), 403, 'You cannot disable your own account.');
        $status = 0;
        $status_name = 'disabled';
        if (filter_var($request->input('is_enable'), FILTER_VALIDATE_BOOLEAN)) {
            $status = 1;
            $status_name = 'enabled';
        }

        if ($user) {
            $user->status = $status;
            $user->pending_email_activation = false;
            $user->save();
            $this->notifyStatusChange($user, 'status');
        }

        return response()->json(['success' => "User $status_name successfully"]);
    }

    public function user_portfolio_handler(Request $request): object
    {
        if (! User::verify_default_admin()) {
            abort(403, 'Access Forbidden');
        }

        $request->validate(['user_id' => ['required', 'integer', 'exists:users,id'], 'is_enable' => ['required', 'boolean']]);
        $user = User::findOrFail($request->input('user_id'));

        $status = 0;
        $status_name = 'disabled';
        if (filter_var($request->input('is_enable'), FILTER_VALIDATE_BOOLEAN)) {
            $status = 1;
            $status_name = 'enabled';
        }

        if ($user) {
            $user->portfolio_status = $status;
            $user->save();
        }

        if ($user->wasChanged('portfolio_status')) {
            $user = User::create_portfolio_id($user->id);
            Mail::to($user->email, $user->name)->queue(new SendAccountStatusNotification($user->name, 'portfolio', (bool) $status, $user->portfolio_id));
        }

        return response()->json(['success' => "User portfolio $status_name successfully"]);
    }

    private function notifyStatusChange(User $user, string $attribute): void
    {
        if ($user->wasChanged($attribute)) {
            Mail::to($user->email, $user->name)->queue(new SendAccountStatusNotification($user->name, 'account', (bool) $user->status));
        }
    }

    public function get_users_ajax(Request $request): object
    {

        $draw = $request->input('draw');

        $columns = ['id', 'role', 'name', 'email', 'created_at', 'updated_at', 'id'];

        $total = User::filtered_users()->count();

        $filtered = $total;

        $limit = $request->input('length');
        $start = $request->input('start');
        $columnName = $columns[$request->input('order.0.column')];
        $orderBy = $request->input('order.0.dir');

        if (empty($request->input('search.value'))) {
            $users = User::filtered_users()
                ->offset($start)
                ->limit($limit)
                ->orderBy($columnName, $orderBy)
                ->get();
        } else {
            $search_text = $request->input('search.value');

            $users = User::filtered_users()
                ->Where(function ($query) use ($search_text) {
                    $query->where('roles.name', 'LIKE', "%{$search_text}%")
                        ->orWhere('users.name', 'LIKE', "%{$search_text}%")
                        ->orWhere('users.email', 'LIKE', "%{$search_text}%")
                        ->orWhere('users.created_at', 'LIKE', "%{$search_text}%")
                        ->orWhere('users.updated_at', 'LIKE', "%{$search_text}%");
                })
                ->offset($start)
                ->limit($limit)
                ->orderBy($columnName, $orderBy)
                ->get();

            $filtered = User::filtered_users()
                ->Where(function ($query) use ($search_text) {
                    $query->where('roles.name', 'LIKE', "%{$search_text}%")
                        ->orWhere('users.name', 'LIKE', "%{$search_text}%")
                        ->orWhere('users.email', 'LIKE', "%{$search_text}%")
                        ->orWhere('users.created_at', 'LIKE', "%{$search_text}%")
                        ->orWhere('users.updated_at', 'LIKE', "%{$search_text}%");
                })
                ->count();
        }
        $data = [];
        foreach ($users as $user) {
            $is_enable_user = $user->status;
            $is_enable_portfolio = $user->portfolio_status;
            $enable_or_disable_user = route('users.enable-or-disable');
            $enable_or_disable_portfolio = route('users.portfolio-enable-or-disable');

            if ($is_enable_user) {
                $user_status_class = 'btn-outline-danger';
                $user_status_label = 'Disable';
            } else {
                $user_status_class = 'btn-outline-success';
                $user_status_label = 'Enable';
            }
            $status_button_user = '<button type="button" class="btn btn-xs btn-block '.$user_status_class.' btn-sm portfolio_status" data-is-enabled="'.$is_enable_user.'" data-id="'.$user->id.'" data-action="'.$enable_or_disable_user.'" onclick="return enable_or_disable_user($(this));">'.$user_status_label.'</button>';

            if ($is_enable_portfolio) {
                $portfolio_status_class = 'btn-outline-danger';
                $portfolio_status_label = 'Disable';
            } else {
                $portfolio_status_class = 'btn-outline-success';
                $portfolio_status_label = 'Enable';
            }
            $status_button_portfolio = '<button type="button" class="btn btn-xs btn-block '.$portfolio_status_class.' btn-sm portfolio_status" data-is-enabled="'.$is_enable_portfolio.'" data-id="'.$user->id.'" data-action="'.$enable_or_disable_portfolio.'" onclick="return enable_or_disable_portfolio($(this));">'.$portfolio_status_label.'</button>';

            $show = route('users.show', $user->id);
            $edit = route('users.edit', $user->id);
            $delete = route('users.destroy', $user->id);

            $row['id'] = ++$start;
            $row['role'] = $user->role_name;
            $row['name'] = $user->name;
            $row['email'] = $user->email;
            $row['status'] = $status_button_user;
            $row['portfolio_status'] = $status_button_portfolio;
            $row['created_at'] = Carbon::parse($user->created_at)->format('d-M-Y');
            $row['updated_at'] = Carbon::parse($user->updated_at)->format('d-M-Y');
            $row['actions'] = '<div class="btn-toolbar" role="toolbar" aria-label="Toolbar with button groups">';
            $row['actions'] .= '<div class="btn-group mr-1 mb-1" role="group">';
            $row['actions'] .= '<a type="button" href="'.$show.'" class="btn btn-xs btn-block btn-outline-info btn-sm">Show</a>';
            $row['actions'] .= '</div>';
            $row['actions'] .= '<div class="btn-group mr-1 mb-1" role="group">';
            $row['actions'] .= '<a type="button" href="'.$edit.'" class="btn btn-xs btn-block btn-outline-warning btn-sm">Edit</a>';
            $row['actions'] .= '</div>';
            $row['actions'] .= '<div class="btn-group mr-1 mb-1" role="group">';
            $row['actions'] .= '<button type="button" class="btn btn-xs btn-block btn-outline-danger btn-sm" data-action="'.$delete.'" onclick="return form_delete($(this));">Delete</button>';
            $row['actions'] .= '</div>';
            $row['actions'] .= '</div>';
            $data[] = $row;
        }

        $response = [
            'draw' => intval($draw),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ];

        return response()->json($response);
    }
}
