<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The SPA was written against Laravel's built-in notifications payload
 * (`data.title` / `data.message` / `read_at`), but this app stores its own
 * `title` / `body` / `is_read` columns. Bridge the two here.
 */
class NotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (string) $this->id,
            'type' => 'app',
            'data' => [
                'title' => $this->title,
                'message' => $this->body,
            ],
            // No read_at column exists; updated_at is when is_read was flipped.
            'read_at' => $this->is_read ? optional($this->updated_at)->toIso8601String() : null,
            'is_read' => (bool) $this->is_read,
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
