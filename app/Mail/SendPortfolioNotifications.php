<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendPortfolioNotifications extends Mailable
{
    use Queueable, SerializesModels;

    public object $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $portfolio_id = $this->user->portfolio_id;
        $site_name = e(config('app.name', 'itechorbit'));
        $site_url = e(url('/'));
        $portfolio_url = e(url('/portfolios/'.$portfolio_id));
        $portfolio_link = "<a style='font-weight: bold;' href=\"$portfolio_url\" target='_blank'>$portfolio_id</a>";
        $name = $this->user->name;
        $message_body = "<span style='font-weight: bold;'>Congratulations!</span> and Welcome to $site_name. Your portfolio has been <span style='font-weight: bold;'>enabled</span> and Portfolio ID- $portfolio_link. Now you are eligible to create your own portfolio in our site. For more details you can visit <a style='font-weight: bold;' href=\"$site_url\" target='_blank'>$site_name</a> site.";
        $subject = 'User portfolio enabled successfully';

        return $this->view('admin.mail_templates.mail', compact('name', 'message_body'))->subject($subject);
    }
}
