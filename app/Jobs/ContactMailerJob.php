<?php

namespace App\Jobs;

use App\Mail\SendContactNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class ContactMailerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public object $user;

    public object $contact;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user, $contact)
    {
        $this->user = $user;
        $this->contact = $contact;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Mail::to($this->user->email)->send(new SendContactNotifications($this->user, $this->contact));
        $this->contact->update(['sent_mail_status' => 1]);
    }
}
