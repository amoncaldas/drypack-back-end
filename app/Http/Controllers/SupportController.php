<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;

class SupportController extends Controller
{
    public function __construct()
    {
    }

    /**
     * Action the returns the attributes translations
     * These translations are used also by the client
     */
    public function langs(Request $request)
    {
        $messages = array_merge(trans('auth.messages'), trans('business'), trans('mail'));
        $attributes = trans('validation.attributes');
        return [
            'attributes' => $attributes,
            'messages' => $messages
        ];
    }
}
