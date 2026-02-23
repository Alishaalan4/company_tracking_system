<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NewUserCredentialsMail extends Mailable implements ShouldQueue
{
    use SerializesModels;

    public $user;
    public $tempPassword;
    public $tempPin;

    public function __construct($user, $tempPassword, $tempPin)
    {
        $this->user = $user;
        $this->tempPassword = $tempPassword;
        $this->tempPin = $tempPin;
    }

    public function build()
    {
        return $this->subject('Your Smart Attendance Account')
            ->view('emails.new_user_credentials');
    }
}
