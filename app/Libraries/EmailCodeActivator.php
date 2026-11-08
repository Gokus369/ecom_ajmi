<?php

namespace App\Libraries;

use CodeIgniter\Shield\Authentication\Actions\EmailActivator;
use CodeIgniter\Shield\Exceptions\RuntimeException;

/** Emails the sign-up code, and keeps the page up if the mail server fails. */
class EmailCodeActivator extends EmailActivator
{
    public function show(): string
    {
        try {
            return parent::show();
        } catch (RuntimeException) {
            // Shield's message holds the customer's address, so it stays out of the log.
            log_message('error', 'The sign-up code could not be emailed. Check the email settings in .env.');
            session()->setFlashdata('error', 'We could not send your code just now. Please try "Send a new code" in a moment.');

            return $this->view(setting('Auth.views')['action_email_activate_show']);
        }
    }
}
