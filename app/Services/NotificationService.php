<?php
namespace App\Services;

use App\Jobs\SendInAppNotificationJob;

class NotificationService
{
    public function notify($userId, $title, $body)
    {
        SendInAppNotificationJob::dispatch($userId, $title, $body);
    }
}