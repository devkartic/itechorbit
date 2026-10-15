<?php

namespace App\Mail;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendPortfolioInquiry extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $owner, public Contact $inquiry) {}

    public function build()
    {
        // Use the configured verified sending address; never spoof the visitor's From address.
        return $this->from(config('mail.from.address'), $this->owner->name.' · Portfolio')
            ->replyTo($this->inquiry->email, $this->inquiry->name)
            ->subject('Portfolio inquiry: '.$this->inquiry->subject)
            ->view('mail.portfolio-inquiry')->text('mail.portfolio-inquiry-text');
    }
}
