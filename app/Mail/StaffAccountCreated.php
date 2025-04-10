<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Staff;

class StaffAccountCreated extends Mailable
{
    use Queueable, SerializesModels;

    public $staff;
    public $temp_password;

    /**
     * Create a new message instance.
     */
    public function __construct(Staff $staff, $temp_password)
    {
        $this->staff = $staff;
        $this->temp_password = $temp_password;
    }

    public function build() {
        return $this->subject('Your Staff Account Has Been Created')
                    ->view('emails.staff_account_created')
                    ->with([
                        'name' => $this->staff->name,
                        'email' => $this->staff->email,
                        'tempPassword' => $this->temp_password, // Fix variable name here
                    ]);
    }
}

