<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\LeaveService;

class LeaveController extends Controller
{
    protected $leaveService;

    public function __construct(LeaveService $leaveService)
    {
        $this->leaveService = $leaveService;
    }

    public function store(Request $request)
    {
        $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after_or_equal:start_date',
            'reason'        => 'nullable|string'
        ]);

        return $this->leaveService->submit(
            $request->user(),
            $request->all()
        );
    }

    public function index(Request $request)
    {
        return $this->leaveService->index($request->user());
    }

    public function update($id, Request $request)
    {
        return $this->leaveService->updateStatus($id, $request->status);
    }

    public function destroy($id)
    {
        return $this->leaveService->delete($id);
    }
}