<?php
namespace App\Services;

use App\Models\Notification;

class NotificationService
{
    /**
     * Writes the notification directly rather than dispatching
     * SendInAppNotificationJob: the queue connection is `database` and there is
     * no worker running in this setup, so queued notifications never appeared.
     * It is a single insert, so there is nothing to gain from deferring it.
     */
    public function notify($userId, $title, $body): void
    {
        Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'body' => $body,
            'is_read' => false,
        ]);
    }
}
