<?php

namespace App\Exceptions;

use Exception;
use Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use \Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tymon\JWTAuth\Exceptions\Exceptions;
use App\Exceptions\BusinessException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use \Illuminate\Auth\AuthenticationException;
use Tymon\JWTAuth\Exceptions\JWTException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Session\TokenMismatchException::class,
        ValidationException::class,
    ];

    protected $headers = [];

    protected $errorKey = 'error';


    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        $this->headers = \DryPack::getCORSHeaders();

        //Exceptions related to the JWT token
        $tokenResponse = $this->handlerTokenExceptions($e);

        if ($tokenResponse !== null) {
            return $tokenResponse;
        }

        //Specific Exceptions with special treatment
        $response = $this->handlerSpecialExceptions($e);

        // For the other exceptions, no special treatment
        // Then a generic response is created
        if ($response === null) {
            $response = $this->buildGenericErrorResponse($e);
        }

        return $this->refreshTokenInResponse($response);
    }

    /**
     * Handle the token exception cases
     * (TokenExpiredException, UnauthorizedHttpException, TokenInvalidException, JWTException and BadRequestHttpException),
     * given back the right response
     *
     * @param \Exception $e
     * @return \Illuminate\Http\Response|null
     */
    protected function handlerTokenExceptions(Exception $e)
    {
        $response = null;

        if ($e instanceof TokenExpiredException) {
            $response = response()->json([$this->errorKey =>'token_expired'], 403, $this->headers);
        }

        // jwt-auth throws UnauthorizedHttpException when the
        // token was not provided or when the token is expired
        if ($e instanceof UnauthorizedHttpException) {
            // In jwt-auth version 1.0.0-rc.1, when a UnauthorizedHttpException is raised, it is a case of
            // token not provided or token expired.
            // As the jwt-auth only differentiate the two cases in the exception message :-(,
            // we have to check it to give back the right response error key
            if ($e->getMessage() == "Token not provided") {
                $response = response()->json([$this->errorKey => 'token_not_provided'], $e->getStatusCode(), $this->headers);
            } else {
                $response = response()->json([$this->errorKey =>'token_expired'], $e->getStatusCode(), $this->headers);
            }
        }

        if ($e instanceof TokenInvalidException) {
            $response = response()->json([$this->errorKey =>'token_invalid'], 403, $this->headers);
        }

        if ($e instanceof TokenBlacklistedException) {
            $response = response()->json([$this->errorKey =>'token_expired'], 403, $this->headers);
        }

        // When there is no token, a JWTException is raised
        if ($e instanceof JWTException) {
            $response = response()->json([$this->errorKey =>'token_absent'], 403, $this->headers);
        }

        // In some cases BadRequestHttpException throws a BadRequestHttpException when a token is not provided
        if ($e instanceof BadRequestHttpException && $e->getMessage() == "Token not provided") {
            $response = response()->json([$this->errorKey => 'token_not_provided'], $e->getStatusCode(), $this->headers);
        }

        return $response;
    }

    /**
     * Handle the special exception cases (ModelNotFoundException, ValidationException, QueryException and BusinessException ),
     * given back the right response
     *
     * @param \Exception $e
     * @return \Illuminate\Http\Response|null
     */
    protected function handlerSpecialExceptions(Exception $e)
    {
        $response = null;

        // When a model was not found by the given ID
        if ($e instanceof ModelNotFoundException) {
            $response = response()->json([$this->errorKey =>'messages.resourceNotFoundError'], 404, $this->headers);
        }

        // When a validation exception is raised
        if ($e instanceof ValidationException) {
            $response = $e->response;
        }

        // When an invalid query is ran
        if ($e instanceof QueryException) {
            Log::debug('Database error: '.$e->getMessage());

            if (strpos($e->getMessage(), 'not-null') !== false) {
                $response = response()->json([$this->errorKey => 'messages.notNullError'], 400, $this->headers);
            }
        }
        // When an business exception is raised by the application
        if ($e instanceof BusinessException) {
            $response = response()->json([$this->errorKey => $e->getMessage()], 400, $this->headers);
        }

        return $response;
    }

    /**
     * Build a generic error  (internal error) when we don't know the error reason
     *
     * @param \Exception $e
     * @return void
     */
    protected function buildGenericErrorResponse(Exception $e)
    {
        $content = [$this->errorKey => 'messages.internalError'];

        if (config('app.debug')) {
            $content = [$this->errorKey => $e->getMessage()];
        }

        return response()
            ->json($content, method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500, $this->headers);
    }

    /**
     * Refresh the token, if it exists, so we can attach it to the response
     *
     * @param \Illuminate\Http\Response $response
     * @return \Illuminate\Http\Response $response
     */
    protected function refreshTokenInResponse($response)
    {
        try {
            $token = \JWTAuth::parseToken()->refresh();

            if ($token !== null) {
                $response = $response->header('Authorization', 'Bearer '. $token);
            }
        } catch (Exception $ex) {
            Log::debug('Request without token');
        }

        return $response;
    }

    /**
     * Override the ExceptionHandler unauthenticated method to convert
     * an authentication exception into a 401 response with an error in json format.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return response()->json(['error' => 'messages.notAuthorized'], 401);
    }
}
