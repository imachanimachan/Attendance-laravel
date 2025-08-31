<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AdminAttendanceService;

class AdminAttendanceController extends Controller
{
    protected $attendanceService;

	public function __construct(AdminAttendanceService $attendanceService)
	{
		$this->attendanceService = $attendanceService;
	}

	public function index(Request $request)
	{
		$data = $this->attendanceService->getAttendanceData($request->query());

		return view('admin.list', $data);
	}

    public function show($id, Request $request)
    {
        $data = $this->attendanceService->getAttendanceDetail($id);

        return view('admin.show', $data);
    }
}
