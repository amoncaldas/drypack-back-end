<?php
/*
 * This file is part of the DryPack Dynamic Authorization
 *
 * @author Amon Santana <amoncaldas@gmail.com>
 */

namespace App\Http\Middleware;

use Closure;
use App\Role;
use App\Authorization\Authorization;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use \Illuminate\Http\Request;

class DynamicPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     * @throws Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     */
    public function handle(Request $request, Closure $next)
    {
        $actionData = $request->route()->getAction();

        if(isset($actionData['controller'])) {
            // Get the controller and action names from the action data
            $actionParts = explode('@',$actionData['controller']);
            $controller = $actionParts[0];
            $action = $actionParts[1];

            // Get the anonymous role
            $anonymousRole = Role::where('slug', Role::anonymousRoleSlug())->first();

            // If the action can be executed for an anonymous, so we do not stop
            if ($anonymousRole != null && Authorization::roleHasPermission($anonymousRole, $controller, $action)) {
                return $next($request);

            } else { // if not, we must check for user/permission

                $user = $this->tryGetUser();

                // Check if a user is authenticated and has the permission
                if (!$user || !$user->hasPermission($controller, $action)) {
                    $msg = Authorization::getDenialMessage($controller, $action);
                    return response()->json(['error' =>$msg], 403); // Forbidden
                } else {
                    return $this->buildResponse($request, $next);
                }
            }
        }

        return $next($request);
    }

    /**
     * Build the response refreshing the token
     *
     * @param Request $request
     * @return Illuminate\Http\JsonResponse $response
     * @throws UnauthorizedHttpException
     */
    protected function buildResponse(Request $request, Closure $next) {
        try {
            $token =  \JWTAuth::parseToken()->refresh();
        } catch (JWTException $e) {
            throw new UnauthorizedHttpException('jwt-auth', $e->getMessage(), $e, $e->getCode());
        }

        $response = $next($request);
        $response = $response->header('Authorization', 'Bearer '. $token);
        return $response;
    }

    /**
     * Try to authenticate and and get the user using the JWTAuth
     *
     * @return \App\user|null
     */
    protected function tryGetUser(){
        try {
            return \JWTAuth::parseToken()->authenticate();
        }
        catch (JWTException $e) {
            // if the token is not provided, we dont need to throw
            // but in the other cases we rethrow and this will be treated
            // in the App\Exceptions\Handler in handlerTokenExceptions
            if($e->getMessage() !== "Token not provided"){
                throw $e;
            }
        }
    }
}
