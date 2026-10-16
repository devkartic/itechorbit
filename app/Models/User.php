<?php

namespace App\Models;

use App\Models\Admin\Basic;
use App\Models\Admin\Experience;
use App\Models\Admin\Post;
use App\Models\Admin\Project;
use App\Models\Admin\Role;
use App\Models\Admin\Service;
use App\Models\Admin\Skill;
use App\Models\Admin\SocialLink;
use App\Models\Admin\Testimonial;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'role_id',
        'status',
        'portfolio_id',
        'portfolio_status',
        'email',
        'password',
    ];

    /**
     * Retrieve active collection unique id for the Users.
     */
    public static function filtering_collections($collection, $attribute_key, $attribute_value): object
    {
        return $collection->filter(function ($item) use ($attribute_key, $attribute_value) {
            return $attribute_value == $item->$attribute_key;
        });
    }

    public static function portfolio_collections_for_user($user_id): object
    {
        $user = self::with(['basic', 'social_links', 'skills', 'services', 'projects', 'experiences', 'testimonials'])->findOrFail($user_id);
        $user->social_links = self::filtering_collections($user->social_links, 'status', 1);
        $user->skills = self::filtering_collections($user->skills, 'status', 1);
        $user->services = self::filtering_collections($user->services, 'status', 1);
        $user->projects = self::filtering_collections($user->projects, 'status', 1);
        $user->experiences = self::filtering_collections($user->experiences, 'status', 1);
        $user->testimonials = self::filtering_collections($user->testimonials, 'status', 1);

        return $user;
    }

    /**
     * Create the portfolio unique id for the Users.
     */
    public static function create_portfolio_id($id): object
    {
        $user = User::find($id);
        if (empty($user->portfolio_id)) {
            $primary_key = sprintf('%05d', $user->id);
            $user->portfolio_id = strtoupper(dechex(Carbon::now()->format('YmdHis').$primary_key));
            $user->save();
        }

        return $user;
    }

    /**
     * Get the posts for the Users.
     */
    public static function verify_default_admin(): bool
    {
        if (! Auth::user()) {
            abort(401, 'Unauthenticated');
        }

        return (int) Auth::user()->role_id === Role::$default_admin;
    }

    public static function can_view_all_user_portfolios(): bool
    {
        return in_array((int) Auth::user()?->role_id, [Role::$default_admin, Role::$admin], true);
    }

    public static function verify_ownership($created_by): bool
    {
        if (! Auth::check()) {
            abort(401);
        }
        if (Auth::user()->id != $created_by) {
            if (! self::verify_default_admin()) {
                abort(403, 'Access Forbidden');
            }
        }

        return true;
    }

    /**
     * Get the posts for the Users.
     */
    public static function filtered_users()
    {
        $user_id = Auth::user()->id;
        $collections = User::select([
            'users.*',
            'roles.name as role_name',
        ])->leftjoin('roles', 'roles.id', '=', 'users.role_id');
        if (! self::verify_default_admin()) {
            $collections->where('users.id', $user_id);
        }

        return $collections;
    }

    /**
     * Get the role for the Users.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the posts for the Users.
     */
    public function posts()
    {
        return $this->hasMany(Post::class)->orderBy('posts.id', 'DESC');
    }

    /**
     * Get the posts for the Users.
     */
    public static function filtered_posts()
    {
        $user_id = Auth::user()->id;
        $collections = Post::select([
            'posts.*',
            'categories.name as category',
            'cb.name as created_by',
            'ub.name as updated_by',
        ])->leftjoin('categories', 'categories.id', '=', 'posts.category_id')
            ->leftjoin('users as cb', 'cb.id', '=', 'posts.user_id')
            ->leftjoin('users as ub', 'ub.id', '=', 'posts.updated_by');
        if (! self::verify_default_admin()) {
            $collections->where('user_id', $user_id);
        }

        return $collections;
    }

    /**
     * Get the basic information for the User.
     */
    public function basic()
    {
        return $this->hasOne(Basic::class);
    }

    /**
     * Get the social links information for the User.
     */
    public function social_links()
    {
        return $this->hasMany(SocialLink::class)->orderBy('social_links.order_number', 'ASC');
    }

    /**
     * Get the skills information for the User.
     */
    public function skills()
    {
        return $this->hasMany(Skill::class)->orderBy('skills.order_number', 'ASC');
    }

    /**
     * Get the services information for the User.
     */
    public function services()
    {
        return $this->hasMany(Service::class)->orderBy('services.order_number', 'ASC');
    }

    /**
     * Get the projects information for the User.
     */
    public function projects()
    {
        return $this->hasMany(Project::class)->orderBy('projects.order_number', 'ASC');
    }

    /**
     * Get the experiences information for the User.
     */
    public function experiences()
    {
        return $this->hasMany(Experience::class)->orderBy('experiences.order_number', 'ASC');
    }

    /**
     * Get the testimonials information for the User.
     */
    public function testimonials()
    {
        return $this->hasMany(Testimonial::class)->orderBy('testimonials.order_number', 'ASC');
    }

    /**
     * Get the emails information for the User.
     */
    public function contacts()
    {
        return $this->hasMany(Contact::class)->orderBy('contacts.id', 'DESC');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
