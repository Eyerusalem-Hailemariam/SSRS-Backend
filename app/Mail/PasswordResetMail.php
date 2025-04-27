<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $token;

    /**
     * Create a new message instance.
     */
    public function __construct($token)
    {
        $this->token = $token; 
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Password Reset Code')
                    ->view('emails.password_reset')
                    ->with([
                        'token' => $this->token
                    ]);
    }
}
