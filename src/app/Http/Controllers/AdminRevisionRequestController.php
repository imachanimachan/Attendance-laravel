<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Illuminate\Support\Carbon;
use App\Models\RestBreak;
use App\Models\AttendanceRevision;
use App\Http\Requests\AttendanceRevisionRequest;
use App\Services\AdminRevisionRequestService;

class AdminRevisionRequestController extends Controller
{
    protected $revisionService;

    public function __construct(AdminRevisionRequestService $revisionService)
    {
        $this->revisionService = $revisionService;
    }

    public function requestList(Request $request, AdminRevisionRequestService $revisionService)
    {
        $tab = $request->query('tab');

        $attendanceRevisions = $revisionService->getRevisionList($tab);

        return view('admin.request', compact('attendanceRevisions'));
    }

    public function requestShow($id)
    {
        [$attendanceRevision, $attendance, $mergedBreaks] = $this->revisionService->getMergedBreaks($id);

        return view('admin.approve', compact('attendanceRevision', 'attendance', 'mergedBreaks'));
    }

    public function request($id, AttendanceRevisionRequest $request)
    {
        $result = $this->revisionService->applyRevision($id, $request->all());

        return redirect()->back()->with('message', $result['message']);
    }

    public function approved($id, Request $request, AdminRevisionRequestService $service)
    {
        return $service->approved($id, $request);
    }
}