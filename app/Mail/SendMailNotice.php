<?php

namespace App\Mail;

use App\Models\MailNoticeCampaign;
use App\Models\MailNoticeRecipient;
use Illuminate\Mail\Mailable;

class SendMailNotice extends Mailable
{
    public function __construct(public MailNoticeCampaign $campaign, public MailNoticeRecipient $recipient) {}

    public function build(): static
    {
        return $this->from($this->campaign->from_address, $this->campaign->from_name)->subject($this->campaign->subject)
            ->view('mail.notice')->text('mail.notice-text');
    }
}
