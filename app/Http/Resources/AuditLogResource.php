<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Flattens the `meta` json bag into the discrete fields the SPA's audit table
 * renders, while keeping the raw description available.
 */
class AuditLogResource extends JsonResource
{
    public function toArray($request): array
    {
        $meta = is_array($this->meta) ? $this->meta : [];

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->whenLoaded('user', fn () => optional($this->user)->name),
            'action' => $this->action,
            'description' => $this->description,

            'model' => $meta['model'] ?? null,
            'model_id' => $meta['model_id'] ?? null,
            'changes' => $meta['changes'] ?? null,
            'ip_address' => $meta['ip'] ?? null,

            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
