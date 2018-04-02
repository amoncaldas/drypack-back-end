<?php

namespace App\Http\Controllers;

use App\User;

use Mail;
use Hash;
use Log;

use Illuminate\Http\Request;
use App\Role;
use App\Mail\ConfirmNewUser;
use App\Http\Requests;
use App\Http\Controllers\CrudController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use App\Exceptions\BusinessException;

class UsersController extends CrudController
{
    public function __construct()
    {
    }

    protected function getModel()
    {
        return User::class;
    }

    /**
     * Apply the query filters before search
     *
     * @param Request $request
     * @param [type] $query
     * @return void
     */
    protected function applyFilters(Request $request, $query)
    {
        $query = $query->with('roles');

        if ($request->has('name')) {
            $query = $query->where('name', 'like', '%'.$request->name.'%');
        }

        if ($request->has('email')) {
            $query = $query->where('email', 'like', '%'.$request->email.'%');
        }

        if ($request->has('nameOrEmail')) {
            $query = $query
                ->where('name', 'like', '%'.$request->nameOrEmail.'%')
                ->orWhere('email', 'like', '%'.$request->nameOrEmail.'%');
        }

        if ($request->has('notUsers') && $request->notUsers  !== null) {
            $query = $query->whereNotIn('id', explode(',', $request->notUsers));
        }
    }

    /**
     * Set the default order before search
     *
     * @param Request $request
     * @param [type] $dataQuery
     * @param [type] $countQuery
     * @return void
     */
    protected function beforeSearch(Request $request, $dataQuery, $countQuery)
    {
        $dataQuery->orderBy('name', 'asc');
    }

    protected function getValidationRules(Request $request, Model $obj)
    {
        $rules = [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users'
        ];

        if (strpos($request->route()->getName(), 'users.update') !== false) {
            $rules['email'] = 'required|email|max:255|unique:users,email,'.$obj->id;
        }

        return $rules;
    }

    /**
     * Loads the user roles after get on specific record
     *
     * @param Request $request
     * @param Model $user
     * @return void
     */
    protected function afterShow(Request $request, Model $user)
    {
        $user->roles = $user->roles()->get()->toArray();
    }

    protected function beforeStore(Request $request, Model $user)
    {
        // Put the password in the container, without cryptography, so it can sent to the to user email
        $user->setPasswordContainer(str_random(10));
        $user->password = bcrypt($user->getPasswordContainer());
    }

    /**
     * Add the old user roles to the request before update the user
     *
     * @param Request $request
     * @param Model $user
     * @return void
     */
    protected function beforeUpdate(Request $request, Model $user)
    {
        // Add the old roles in the request array to be used in the audit save
        // by default the audit do not store relations one to many
        $request->merge(array('oldRoles' => array_pluck($user->roles()->get()->toArray(), 'slug')));
    }

    /**
     * Before delete check if the the user can be removed
     *
     * @param Request $request
     * @param Model $user
     * @return void
     */
    protected function beforeDestroy(Request $request, Model $user) {
        if($user->isFirstAdmin()) {
            throw new BusinessException('user_can_not_be_removed');
        }

        if (\Auth::user()->id === $user->id) {
            throw new BusinessException('remove_yourself_error');
        }
    }

    /**
     * After saving a user, store an audit describing the changes/new entry
     *
     * @param Request $request
     * @param Model $user
     * @return null|string
     */
    protected function afterSave(Request $request, Model $user)
    {
        $roles_id_to_save = array_pluck(Input::only('roles')["roles"], 'id');

        // If the user is first admin, then we have to ensure that it has the admin role
        // if not, we can be in a deadlock, where no user will able perform admin tasks
        if($user->isFirstAdmin()) {
            $admin_role_id = Role::where("slug", Role::defaultAdminRoleSlug())->first()->id;
            if(!in_array($admin_role_id, $roles_id_to_save)) {
                $roles_id_to_save[] = $admin_role_id;
                $request->merge(["warning"=>'mandatory_admin_profile_auto_added']);
            }
        }
        // Save the user roles relation using the incoming input roles ID
        $user->roles()->sync($roles_id_to_save);

        // Get the new roles list
        $newRoles = $user->roles()->get()->toArray();

        // Transforms the roles in a array of roles slug
        $new_roles_slugs = array_pluck($newRoles, 'slug');

        // store the roles change in the audit
        $user->storeAudit('updated', 'roles', $request->oldRoles, $new_roles_slugs);
        $user->roles = $newRoles;
    }

    /**
     * After store (only new users), send a confirming e-mail with login and temporary password
     *
     * @param Request $request
     * @param Model $user
     * @return void
     */
    protected function afterStore(Request $request, Model $user)
    {
        try{
            Mail::to($user)->send(new ConfirmNewUser($user));
        } catch(\Exception $ex) {
            // if it is not possible to send the email, a warning header will be added to the request
            $request->merge(["warning"=>'new_registration_email_could_not_be_sent']);
        }
    }


    /**
     * Updates the profile of the current logged user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        // Validate the required and unique fields
        $this->validate($request, [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'password' => 'confirmed|min:6',
        ]);

        $user->fill(Input::only('name', 'email', 'bio'));

        // The locale is saved for the case when scheduled services
        // are ran to send e-mails to users. In this case, it is needed
        // to determine the user locale to compose the e-mails
        // according it right language, without passing for the i18n request middleware
        // in this case it must be checked the $user->locale, if it is not defined (it can be null), use the
        // default, with env("DEFAULT_LOCALE");
        if($request->has('locale')){
            if($user->locale !== $request->locale){
                $user->locale = $request->locale;
            }
        }

        // Hash the new password
        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        //get the roles to return do view
        $user->roles = $user->roles()->get()->toArray();

        return $user;
    }

    public function registerNewsLetterSubscriberUser(Request $request) {
        $this->validate($request, [
            'name'=>'min:3',
            'email'=>'required|unique:users,email'
        ]);

        $email = $request->input('email');
        $existingUser = User::where('email',$email)->first();
        $subscriberRole = Role::where('slug','=',Role::newsSubscriberRoleSlug())->first();
        if(!$existingUser){
            $user = new User();
            $user->fill(Input::only('name','email'));
            $user->save();
            $user->roles()->save($subscriberRole);
        } else {
            $existingUser->roles()->save($subscriberRole);
        }
    }
}
