<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Notification;

class SendInAppNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

 protected $userId;
    protected $title;
    protected $body;

    public function __construct($userId, $title, $body)
    {
        $this->userId = $userId;
        $this->title  = $title;
        $this->body   = $body;
    }

    public function handle()
    {
        Notification::create([
            'user_id' => $this->userId,
            'title'   => $this->title,
            'body'    => $this->body,
        ]);
    }
}
