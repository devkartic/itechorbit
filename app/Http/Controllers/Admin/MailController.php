<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;

class MailController extends Controller
{
    protected ?string $mail_from_address;

    protected ?string $mail_from_name;

    protected ?string $mail_to_address;

    protected ?string $mail_to_name;

    protected ?string $subject;

    protected ?array $data;

    public function __construct($user, $subject, $data)
    {
        $this->mail_from_address = env('MAIL_FROM_ADDRESS');
        $this->mail_from_name = env('MAIL_FROM_NAME');
        $this->mail_to_address = $user->email;
        $this->mail_to_name = $user->name;
        $this->subject = $subject;
        $this->data = $data;
    }

    public function send_mail()
    {
        Mail::send('legacy.admin.mail_templates.mail', $this->data, function ($mail) {
            $mail->to($this->mail_to_address, $this->mail_to_name)->subject($this->subject);
            $mail->from($this->mail_from_address, $this->mail_from_name);
        });
    }
}
