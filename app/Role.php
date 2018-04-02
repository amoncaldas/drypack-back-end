<?php
/*
 * This file is part of the DryPack Dynamic Authorization
 *
 * @author Amon Santana <amoncaldas@gmail.com>
 */


namespace App;

use App\User;
use App\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use App\Authorization\Action;
use App\Authorization\Resource;

/**
 * App\Role
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\User[] $users
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Permission[] $permissions
 * @property-read \Illuminate\Database\Eloquent\Collection|\OwenIt\Auditing\Log[] $logs
 * @method static \Illuminate\Database\Query\Builder|\App\Role whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Role whereTitle($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Role whereSlug($value)
 */
class Role extends BaseModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'roles';
    public $timestamps = false;

    protected $fillable = ['title','slug'];

    /**
     * Get the default news subscriber Role slug
     *
     * @return string
     */
    public static function newsSubscriberRoleSlug(){
        return Config::get('authorization.default_roles.NEWS_SUBSCRIBER_ROLE_SLUG');
    }

    /**
     * Get the default anonymous Role slug
     *
     * @return string
     */
    public static function anonymousRoleSlug(){
        return Config::get('authorization.default_roles.ANONYMOUS_ROLE_SLUG');
    }

    /**
     * Get the default admin Role slug
     *
     * @return string
     */
    public static function defaultAdminRoleSlug(){
        return Config::get('authorization.default_roles.ADMIN_ROLE_SLUG');
    }

    /**
     * Get the default basic Role slug
     *
     * @return string
     */
    public static function defaultBasicRoleSlug(){
        return Config::get('authorization.default_roles.BASIC_ROLE_SLUG');
    }

    /**
     * Check if role is removable
     *
     * @return boolean
     */
    public function isRemovable() {
        return $this->users->count() === 0 && $this->slug !== self::anonymousRoleSlug() && $this->slug !== self::defaultAdminRoleSlug();
    }

    /*
    |--------------------------------------------------------------------------
    | Relationship Methods
    |--------------------------------------------------------------------------
    */

    /**
     * many-to-many relationship method.
     *
     * @return QueryBuilder
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * many-to-many relationship method.
     *
     * @return QueryBuilder
     */
    public function actions()
    {
        return $this->belongsToMany('\App\Authorization\Action', 'role_actions', 'role_id', 'action_id');
    }

    /**
     * Override toArray method
     *
     * @return Array
     */
    public function toArray() {
        $data = parent::toArray();
        $actions = $this->actions;
        $data['actions'] = $actions->toArray();
        $data['can_be_removed'] = $this->isRemovable();
        return $data;
    }
}
