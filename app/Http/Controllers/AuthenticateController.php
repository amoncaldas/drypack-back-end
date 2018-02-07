<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\User;

class AuthenticateController extends Controller
{

    public function __construct()
    {
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->only('email', 'password');

        $this->validate($request, [
            'email' => 'required',
            'password' => 'required'
        ]);

        try {
            // verify the credentials and create a token for the user
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'messages.login.invalidCredentials'], 401);
            }
        } catch (JWTException $e) {
            // something went wrong
            return response()->json(['error' => 'messages.login.unknownError'], 500);
        }

        // First we authenticate the user, then we check if it has the permission
        // because the permissions are based in the user's role
        // If the user does not has the permission, the middleware will raise an exception
        $this->middleware('dyn.permission');

        // if no errors are encountered we can return a JWT
        return response()->json(compact('token'));
    }

    public function getAuthenticatedUser()
    {
        $user = \Auth::user();

        // Get simple string array with only a slug
        $user->roles = $user->roles()->get()->toArray();

        // The token is valid and we have found the user via the sub claim
        return response()->json(compact('user'));
    }
}
