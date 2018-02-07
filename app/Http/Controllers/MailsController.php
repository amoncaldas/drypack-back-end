<?php

namespace App\Http\Controllers;

use Mail;
use App\Mail\AdminNotification;
use App\Http\Controllers\Controller;
use App\Http\Requests;

use Illuminate\Http\Request;

class MailsController extends Controller
{

    public function __construct()
    {
    }

    /**
     * Send the e-mail message to one or more e-mail
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array empty in case of success or a list of the fails
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'users.*.email' => 'required|email',
            'subject' => 'required',
            'message' => 'required'
        ]);

        $mailData = $request->only('subject', 'message');

        Mail::to($request->users)->send(new AdminNotification($mailData));

        $failure = array();

        if (count(Mail::failures()) > 0) {
            foreach (Mail::failures as $emails) {
                array_push($failure, $emails);
            }
        }

        return $failure;
    }
}
