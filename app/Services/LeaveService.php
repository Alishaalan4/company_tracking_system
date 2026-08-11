<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\Attendance;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeaveService
{
    public function submit($user, $data)
    {
        // Overlap prevention. A rejected request must not permanently block a
        // re-submission for the same dates, so only pending/approved count.
        $overlap = LeaveRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('start_date', '<=', $data['end_date'])
            ->whereDate('end_date', '>=', $data['start_date'])
            ->exists();

        if ($overlap) {
            return response()->json(['message' => 'Overlapping leave'], 422);
        }

        $leave = LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $data['leave_type_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'reason' => $data['reason'] ?? null,
            'status' => 'pending'
        ]);

        AuditLogService::record(
            'leave.submitted',
            "Requested leave {$leave->start_date->toDateString()} to {$leave->end_date->toDateString()}",
            $leave
        );

        return response()->json($leave);
    }

    public function updateStatus($id, $status)
    {
        if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
            return response()->json(['message' => 'Invalid status value'], 422);
        }

        $leave = LeaveRequest::findOrFail($id);
        $previous = $leave->status;
        $leave->status = $status;
        $leave->save();

        if ($status === 'approved') {
            $this->overrideAttendance($leave);
        }

        AuditLogService::record(
            "leave.{$status}",
            "Leave request #{$leave->id} {$status}",
            $leave,
            ['before' => $previous, 'after' => $status]
        );

        if ($status !== $previous && in_array($status, ['approved', 'rejected'], true)) {
            $range = $leave->start_date->toDateString() . ' to ' . $leave->end_date->toDateString();

            app(NotificationService::class)->notify(
                $leave->user_id,
                'Leave request ' . $status,
                "Your leave request for {$range} has been {$status}."
            );
        }

        return response()->json($leave);
    }

    private function overrideAttendance($leave)
    {
        $period = Carbon::parse($leave->start_date)
            ->daysUntil($leave->end_date->addDay());

        foreach ($period as $date) {

            Attendance::updateOrCreate(
                [
                    'user_id' => $leave->user_id,
                    'date' => $date->toDateString()
                ],
                [
                    'is_absent' => false,
                    'check_in_at' => null,
                    'check_out_at' => null
                ]
            );
        }
    }

    public function index($user)
    {
        if ($user->isAdmin()) {
            return LeaveRequest::latest()->paginate(30);
        }

        return LeaveRequest::where('user_id', $user->id)
            ->latest()
            ->paginate(30);
    }

    public function delete($id)
    {
        LeaveRequest::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
