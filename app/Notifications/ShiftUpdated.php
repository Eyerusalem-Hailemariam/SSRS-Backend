<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Shift;

class ShiftUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    protected $shift;
    protected $action;

    public function __construct(Shift $shift, $action)
    {
        $this->shift = $shift;
        $this->action = $action;
    }

    public function via($notifiable)
    {
        return ['mail', 'database']; // You can add 'sms', 'slack', etc.
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject("Your shift has been {$this->action}")
            ->line("Your shift has been {$this->action}.")
            ->line("Start Time: {$this->shift->start_time}")
            ->line("End Time: {$this->shift->end_time}")
            ->action('View Shift', url('/shifts')) // Change this to your actual shift page
            ->line('Thank you for using our system!');
    }

    public function toArray($notifiable)
    {
        return [
            'message' => "Your shift has been {$this->action}.",
            'shift_id' => $this->shift->id,
            'start_time' => $this->shift->start_time,
            'end_time' => $this->shift->end_time,
        ];
    }
}
