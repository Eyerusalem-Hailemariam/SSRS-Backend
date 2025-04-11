<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp; // Declare OTP as a public variable

    /**
     * Create a new message instance.
     */
    public function __construct($otp)
    {
        $this->otp = $otp; 
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->view('emails.otp') 
                    ->with(['otp' => $this->otp]); 
    }
}
