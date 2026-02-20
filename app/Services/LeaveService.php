<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeaveService
{
    public function submit($user, $data)
    {
        // Overlap prevention
        $overlap = LeaveRequest::where('user_id', $user->id)
            ->where(function ($q) use ($data) {
                $q->whereBetween('start_date', [$data['start_date'], $data['end_date']])
                  ->orWhereBetween('end_date', [$data['start_date'], $data['end_date']]);
            })
            ->exists();

        if ($overlap) {
            return response()->json(['message' => 'Overlapping leave'], 422);
        }

        $leave = LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $data['leave_type_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'reason' => $data['reason'],
            'status' => 'pending'
        ]);

        return response()->json($leave);
    }

    public function updateStatus($id, $status)
    {
        $leave = LeaveRequest::findOrFail($id);
        $leave->status = $status;
        $leave->save();

        if ($status === 'approved') {
            $this->overrideAttendance($leave);
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