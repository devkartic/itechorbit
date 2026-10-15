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
        return $this->replyTo($this->contact->email, $this->contact->name)
            ->subject($this->contact->subject)
            ->view('mail.business-inquiry', ['owner' => $this->user, 'inquiry' => $this->contact])
            ->text('mail.business-inquiry-text');
    }
}
