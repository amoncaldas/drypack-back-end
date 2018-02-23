<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSending;

class MailListener
{
    /**
     * Create the mail event listener.
     */
    public function __construct()
    {
        // ...
    }

    /**
     * Handle the mail sending event.
     *
     * @param MessageSending $event
     * @return void
     */
    public function handle(MessageSending $event)
    {
        // if the log mail is disabled, the mail is not even logged in the log file
        // this is useful, for exemple, in automate testing environments
        // where it is not possible  to write files
        // The mails will not be saved in the log file, but considered sent
        $disableLogmail = env("DISABLE_LOG_MAIL");
        if($disableLogmail === true) {
            return false;
        }
    }
}
