<?php

namespace App\Mail;

// Retained for portfolio emails already present in the queue before the shared theme.
class SendPortfolioNotifications extends SendAccountStatusNotification
{
    public function __construct($user)
    {
        parent::__construct($user->name, 'portfolio', true, $user->portfolio_id);
    }
}
