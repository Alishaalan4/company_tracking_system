<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Presents an attendance row in the shape the SPA consumes: `check_in` /
 * `check_out` rather than the raw `*_at` columns, plus the derived `duration`
 * and `status` that have no column of their own.
 */
class AttendanceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->whenLoaded('user', fn () => $this->user->name),
            'department' => $this->whenLoaded(
                'user',
                fn () => optional($this->user->department)->name
            ),
            'date' => optional($this->date)->toDateString(),

            'check_in' => optional($this->check_in_at)->toIso8601String(),
            'check_out' => optional($this->check_out_at)->toIso8601String(),

            'duration' => $this->durationInMinutes(),
            'status' => $this->status(),

            'is_late' => (bool) $this->is_late,
            'left_early' => (bool) $this->left_early,
            'is_absent' => (bool) $this->is_absent,
        ];
    }

    /**
     * Worked minutes, or null while the day is still open.
     */
    private function durationInMinutes(): ?int
    {
        if (!$this->check_in_at || !$this->check_out_at) {
            return null;
        }

        return max(0, $this->check_in_at->diffInMinutes($this->check_out_at));
    }

    private function status(): string
    {
        if ($this->is_absent) {
            return 'absent';
        }

        if (!$this->check_in_at) {
            return 'pending';
        }

        if ($this->is_late) {
            return 'late';
        }

        if ($this->left_early) {
            return 'early';
        }

        return 'ontime';
    }
}
