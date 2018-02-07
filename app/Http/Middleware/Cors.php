<?php

namespace App\Http\Middleware;

use Closure;

class Cors
{
    /**
     * Middleware that treats the CORS, adding them to the header
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // Get all the CORS headers
        $headers = \DryPack::getCORSHeaders();

        // Add all the CORS headers to the response header
        foreach ($headers as $key => $value) {
            $response->header($key, $value);
        }

        return $response;
    }

}
