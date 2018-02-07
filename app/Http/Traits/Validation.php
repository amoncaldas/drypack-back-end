<?php
/**
 * CrudController is a shared base controller that provides a CRUD basis for Laravel applications.
 * Since laravel 5.5 the throwValidationException method does not exist more in Illuminate\Foundation\Validation\ValidatesRequests,
 * so, we recreated it in this trait, as well as buildFailedValidationResponse, with some adaptations
 *
 * @author Jamie Rumbelow <jamie@jamierumbelow.net>, Amon Caldas <amoncaldas@gmail.com>
 * @license http://opensource.org/licenses/MIT
 */

namespace App\Http\Traits;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;

/**
 * Validation
 *
 * @internal
 * @uses \App\Http\Controllers\CrudController
 * @used-by \App\Http\Controllers\CrudController
 */
trait Validation
{
    use ValidatesRequests;

    /**
     * We're overriding the main point of access to Laravel's ValidatesRequests, because we wat
     * to support the validation of data arrays instead of request objects.
     *
     * @param array|\Illuminate\Http\Request|Illuminate\Database\Eloquent\Model $data The data to validate (or the request object)
     * @param array $rules The validation rules to run on the data
     * @param array $messages
     * @param array $customAttributes
     * @return void
     * @throws \Illuminate\Validation\ValidationException
     **/
    public function validate($data, array $rules, array $messages = [], array $customAttributes = [])
    {
        if ( $data instanceof Request ) {
            $data = $data->all();
        } elseif($data instanceof Model) {
            $data = $data->getAttributes();
        }

        $this->validator = $this->getValidationFactory()->make($data, $rules, $messages, $customAttributes);

        if ($this->validator->fails()) {
            $this->throwValidationException(request(), $this->validator);
        }
    }

     /**
     * Throw the failed validation exception.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function throwValidationException(Request $request, $validator)
    {
        $validatorMessages = $validator->errors()->getMessages();
        $failedValidationResponse = $this->buildFailedValidationResponse($request, $validatorMessages);
        throw new ValidationException($validator, $failedValidationResponse);
    }

    /**
     * Create the response for when a request fails validation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $errors
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function buildFailedValidationResponse(Request $request, array $errors)
    {
        if ($request->expectsJson()) {
            return new JsonResponse($errors, 422);
        }

        return redirect()->to($this->getRedirectUrl())
                        ->withInput($request->input())
                        ->withErrors($errors, $this->errorBag());
    }
}
