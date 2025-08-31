<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\AttendanceService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Requests\AttendanceRevisionRequest;


class AttendanceController extends Controller
{
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function index()
    {
        $attendance = $this->attendanceService->getTodayAttendance();
        return view('attendance.index', compact('attendance'));
    }

    public function startAttendance()
    {
        $user = Auth::user();
        $this->attendanceService->startAttendance($user);

        return redirect()->back();
    }

    public function startBreak()
    {
        $this->attendanceService->startBreak();

        return redirect()->back();
    }

    public function endBreak()
    {
        $this->attendanceService->endBreak();
        return redirect()->back();
    }

    public function endAttendance()
    {
        $this->attendanceService->endAttendance();
        return redirect()->back();
    }

    public function showList(Request $request)
    {
        $user = Auth::user();

        $data = $this->attendanceService->getMonthlyAttendances(
            $user,
            $request->input('year'),
            $request->input('month')
        );

        return view('attendance.list', $data);
    }

    public function show($id, Request $request)
    {
        $data = $this->attendanceService->getAttendanceWithRevisionStatus($id);
        return view('attendance.show', $data);
    }

    public function request($id, AttendanceRevisionRequest $request)
    {
        $result = $this->attendanceService->requestRevision($id, $request);

        return redirect()->back()->with('message', $result['message']);
    }

    public function requestList(Request $request)
    {
        $user = Auth::user();
        $tab = $request->query('tab');

        $attendanceRevisions = $this->attendanceService->getRequestList($user, $tab);

        return view('attendance.request', compact('attendanceRevisions'));
    }
}