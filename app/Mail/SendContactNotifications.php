<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendContactNotifications extends Mailable
{
    use Queueable, SerializesModels;

    public object $user;

    public object $contact;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $contact)
    {
        $this->user = $user;
        $this->contact = $contact;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $name = $this->user->name;
        $message_from = e($this->contact->name);
        $site_name = e(config('app.name', 'itechorbit'));
        $home_url = e(url('/home'));
        $message_body = "<b class='text-capitalize'>$message_from</b> just message you! To see the message details browse at <a style='font-weight: bold;' href=\"$home_url\" target='_blank'>$site_name</a> Contact Mail / Inbox.";
        $subject = $this->contact->subject;

        return $this->view('admin.mail_templates.mail', compact('name', 'message_body'))->subject($subject);
    }
}
