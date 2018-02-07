<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class RecoveryPassword extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $token;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $token)
    {
        $this->user = $user;
        $this->token = $token;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->subject(\Lang::get("mail.password_recovery"))
            ->view('emails.users.recoveryPassword')
            ->with([
                'user' => $this->user,
                'token' => $this->token,
                'url' => config('app.url'),
                'appName' => config('app.name')
            ]);
    }
}
