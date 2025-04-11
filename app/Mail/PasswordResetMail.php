<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $resetLink;

    /**
     * Create a new message instance.
     */
    public function __construct($token)
    {

        $this->resetLink = env('FRONTEND_URL') . '/reset-password?token=' . $token;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Reset Your Password')
                    ->view('emails.password_reset')
                    ->with([
                        'resetLink' => $this->resetLink
                    ]);
    }
}
