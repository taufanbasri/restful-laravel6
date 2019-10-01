<?php

namespace App\Listeners;

use App\Events\MailNotification;
use App\Notifications\MailChangedNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class UserChangedMailListener
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(MailNotification $event)
    {
        if ($event->user instanceof MustVerifyEmail && ! $event->user->hasVerifiedEmail()) {
            $event->user->notify(new MailChangedNotification($event->user));
        }
    }
}
