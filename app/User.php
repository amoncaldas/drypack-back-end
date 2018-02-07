<?php
/*
 * This file is part of the DryPack Dynamic Authorization
 *
 * @author Amon Santana <amoncaldas@gmail.com>
 */

namespace App;

use App\BaseModel;
use App\Mail\RecoveryPassword;

use Illuminate\Auth\Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Tymon\JWTAuth\Contracts\JWTSubject as JWTSubject;
use Mail;
use App\Authorization\Action;
use App\Authorization\Authorization;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Contracts\UserResolver;
use Illuminate\Support\Arr;

/**
 * App\User
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $remember_token
 * @property string $image
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Role[] $roles
 * @property-read \Illuminate\Database\Eloquent\Collection|\OwenIt\Auditing\Log[] $logs
 * @method static \Illuminate\Database\Query\Builder|\App\User whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\User whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\User whereEmail($value)
 * @method static \Illuminate\Database\Query\Builder|\App\User wherePassword($value)
 * @method static \Illuminate\Database\Query\Builder|\App\User whereRememberToken($value)
 * @method static \Illuminate\Database\Query\Builder|\App\User whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\User whereImage($value)
 */
class User extends BaseModel implements AuthenticatableContract, CanResetPasswordContract, JWTSubject, UserResolver, AuditableContract
{
    use Authenticatable, Authorizable, Notifiable, CanResetPassword;

    /**
     * Attributes that must not be shown in dynamic query
     *
     * @var array
     */
    protected $hideAttributesInDynamicQuery = ['password', 'image', 'remember_token'];

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'email', 'image', 'locale'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];


    //protected $dontKeepLogOf = ['password', 'remember_token'];

    /**
     * Attribute used to temporally store the user password to send it via e-mail
     *
     * @var string
     */
    private $passwordContainer;

    /**
     * Get the password stored in the container
     *
     * @return string
     */
    public function getPasswordContainer()
    {
        return $this->passwordContainer;
    }

    /**
     * Set the password in the container
     *
     * @param string $password_container
     * @return void
     */
    public function setPasswordContainer($password_container)
    {
        $this->passwordContainer = $password_container;
    }

    /**
     * Get the JWT identifier for the JWTAuth component
     * Implements the method required by the interface
     * Tymon\JWTAuth\Contracts\JWTSubject
     *
     * @return integer model's primary key
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Implements the method required by the interface
     * Tymon\JWTAuth\Contracts\JWTSubject. Here is the place to
     * return additional claims that will be included in the token.
     * At the moment we don't need to add any claim
     *
     * @return void
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Should the timestamps be audited?
     *
     * @var bool
     */
    protected $auditTimestamps = true;

    /**
     * {@inheritdoc}
     */
    public function transformAudit(array $data): array
    {
        if (Arr::has($data, 'new_values.role_id')) {
            Arr::set($data, 'new_values.role_name',  $this->role->name);
        }
        return $data;
    }

     /**
     * Attributes to exclude from the Audit.
     *
     * @var array
     */
    protected $auditExclude = [
        'password', 'remember_token', 'image', 'locale'
    ];

    /**
     * Checks if the user has a role or a list of roles
     *
     * @param  String role slug of a role
     * @param boolean if must have all roles in parameter $roles
     * @return Boolean true if has role, otherwise false
     */
    public function hasRole($roles = null, $all = false)
    {
        return !is_null($roles) && $this->checkRole($roles, $all);
    }

    /**
     * Check if the roles matches with any role user has
     *
     * @param  String roles slug of a role
     * @return Boolean true if role exists, otherwise false
     */
    protected function checkRole($roles, $all)
    {
        $userRoles = array_pluck($this->roles()->get()->toArray(), 'slug');

        $roles = is_array($roles) ? $roles : [$roles];

        if($all) {
            return count(array_intersect($userRoles, $roles)) == count($roles);
        } else {
            return count(array_intersect($userRoles, $roles));
        }
    }

    /**
    * Send the password reset notification.
     *
    * @param  string  $token
    * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        Mail::to($this->email)->send(new RecoveryPassword($this, $token));
    }

    /*
    |--------------------------------------------------------------------------
    | Relationship Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Many-To-Many Relationship Method for accessing the User->roles
     *
     * @return QueryBuilder Object
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Verifies if the current user has the permission to run an action in a controller
     *
     * @return boolean
     */
    public function hasPermission($controller, $action)
    {
        return Authorization::hasPermission($controller, $action, $this);
    }

    /**
     * Retrieve the current user id.
     * Implemented the method required by the interface AuditableContract
     *
     * @return int $id
     */
    public static function resolveId() {
        $user = \Auth::user();
        if(isset($user)) {
            return $user->id;
        }
    }

    /**
     * Check if the user is the default/first admin
     *
     * @return boolean
     */
    public function isFirstAdmin() {
        $firstAdmin = self::whereHas('roles', function ($query) {
            $query->where('slug', Role::defaultAdminRoleSlug());
        })->orderBy("id", "asc")->first();

        if (isset($firstAdmin->id)) {
            return $this->id === $firstAdmin->id;
        }
        return false;
    }

    /**
     * Get the default admin name
     *
     * @return boolean
     */
    public static function defaultAdminName() {
       return env('DEFAULT_ADMIN_USER_NAME');
    }

    /**
     * Override the base toArray method to include custom attributes
     *
     * @return array with model's data
     */
    public function toArray() {
        $data = parent::toArray();
        $data['roles'] = $this->roles()->get()->toArray();
        $data['allowed_actions'] = Authorization::userAllowedActions($this);
        return $data;
    }
}
