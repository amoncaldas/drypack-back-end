<?php namespace App\Http\Middleware;

use Closure, Response, Request;
use Illuminate\Contracts\Auth\Guard;

class DevelopAuth
{
    protected $auth;
    /**
     * Creates a new instance of the middleware.
     *
     * @param Guard $auth
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->session()->has('develop-auth') ||
            (Request::getUser() === getenv('DEVELOPER_ID') &&
             Request::getPassword() === getenv('DEVELOPER_PASSWORD') ) ) {

            $request->session()->put('develop-auth', true);

            return $next($request);
        } else {
            $headers = array('WWW-Authenticate' => 'Basic');

            return Response::make('Invalid credentials.', 401, $headers);
        }
    }
}
