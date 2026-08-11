<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Includes the requester and leave type, which the admin queue needs to show
 * who asked for what. The raw model exposed neither, so every row rendered as
 * an anonymous "Leave Request".
 */
class LeaveRequestResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->whenLoaded('user', fn () => optional($this->user)->name),
            'leave_type_id' => $this->leave_type_id,
            'leave_type' => $this->whenLoaded('leaveType', fn () => [
                'id' => optional($this->leaveType)->id,
                'name' => optional($this->leaveType)->name,
                'annual_limit' => optional($this->leaveType)->annual_limit,
            ]),
            'start_date' => optional($this->start_date)->toDateString(),
            'end_date' => optional($this->end_date)->toDateString(),
            'days' => $this->start_date && $this->end_date
                ? $this->start_date->diffInDays($this->end_date) + 1
                : null,
            'reason' => $this->reason,
            'status' => $this->status,
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
