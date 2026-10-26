<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;

class SendAccountStatusNotification extends Mailable implements ShouldQueue
{
    use Queueable;

    // Snapshot the change so later edits cannot change the meaning of a queued email.
    public function __construct(
        public string $recipientName,
        public string $kind,
        public bool $enabled,
        public ?string $portfolioId = null,
    ) {
        $this->afterCommit();
    }

    public function build(): static
    {
        $portfolio = $this->kind === 'portfolio';
        $title = 'Your '.$this->kind.' has been '.($this->enabled ? 'enabled' : 'disabled');
        $description = $portfolio
            ? ($this->enabled ? 'Your portfolio is available to visitors while your account is active.' : 'Your portfolio is no longer available to visitors. Your saved portfolio content has been kept.')
            : ($this->enabled ? 'You can sign in and access your account again. Portfolio visibility follows your portfolio settings.' : 'You can no longer access your account, and your portfolio is no longer available to visitors.');
        $actionUrl = ! $this->enabled ? url('/contact/info')
            : ($portfolio && $this->portfolioId ? url('/portfolios/'.rawurlencode($this->portfolioId)) : url('/login'));
        $actionLabel = ! $this->enabled ? 'Contact our team' : ($portfolio ? 'View your portfolio' : 'Sign in');

        return $this->subject($title)->view('mail.account-status', compact('title', 'description', 'actionUrl', 'actionLabel'))
            ->text('mail.account-status-text');
    }
}
