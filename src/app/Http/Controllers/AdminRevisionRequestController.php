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

    public function approved($id, Request $request)
    {
        $attendance = Attendance::with('breaks')->find($id);

        $requestClockIn = Carbon::parse($request->input('clockIn'));
        $requestClockOut = Carbon::parse($request->input('clockOut'));

        $currentClockIn = Carbon::parse($attendance->clock_in);
        $currentClockOut = Carbon::parse($attendance->clock_out);

        $updateData = [];

        if ($requestClockIn !== null && !$currentClockIn->eq($requestClockIn)) {
            $updateData['clock_in'] = $requestClockIn;
        }
        if ($requestClockOut !== null && !$currentClockOut->eq($requestClockOut)) {
            $updateData['clock_out'] = $requestClockOut;
        }

        if(!empty($updateData)){
            $updateClockIn = $updateData['clock_in'] ?? $currentClockIn;
            $updateClockOut = $updateData['clock_out'] ?? $currentClockOut;

            $totalBreakMinutes = $attendance->breaks->sum('total_break_time');

            $updateData['total_work_time'] = $updateClockIn->diffInMinutes($updateClockOut) - $totalBreakMinutes;

            $attendance->update($updateData);
        }

        $revision = AttendanceRevision::where('attendance_id', $attendance->id)
            ->latest('id')
            ->first();
        $hasAttendanceUpdate = (!empty($updateData));
        $hasBreakUpdate = false;

        $breakInput = $request->input('breaks',[]);
        $currentBreaks = $attendance->breaks->keyBy('display_order');

        foreach($breakInput as $order => $input){
            $inputStart = $input['start'] ?? null;
            $inputEnd = $input['end'] ?? null;

            $newStart = $inputStart ? Carbon::parse($inputStart) : null;
            $newEnd = $inputEnd ? Carbon::parse($inputEnd) : null;

            $existingBreak = $currentBreaks->get($order);

            if(is_null($newStart) && is_null($newEnd)){
                if($existingBreak){
                    $existingBreak->delete();
                    $hasBreakUpdate = true;
                }
                continue;
            }

            if(!$existingBreak){
                $totalBreakTime = $newStart->diffInMinutes($newEnd);

                RestBreak::create([
                    'attendance_id' => $attendance->id,
                    'break_start' => $newStart,
                    'break_end' => $newEnd,
                    'total_break_time' => $totalBreakTime,
                    'display_order' => $order,
                ]);
                $hasBreakUpdate = true;

            }else{
            $startChanged = !$existingBreak->break_start->eq($newStart);
            $endChanged = !$existingBreak->break_end->eq($newEnd);

                if ($startChanged && !$endChanged) {
                    $totalBreakTime = $newStart->diffInMinutes($existingBreak->break_end);

                    $existingBreak->update([
                        'break_start' => $newStart,
                        'total_break_time' => $totalBreakTime,
                    ]);
                    $hasBreakUpdate = true;
                    continue;
                }

                if (!$startChanged && $endChanged) {
                    $totalBreakTime = $existingBreak->break_start->diffInMinutes($newEnd);

                    $existingBreak->update([
                        'break_end' => $newEnd,
                        'total_break_time' => $totalBreakTime,
                    ]);
                    $hasBreakUpdate = true;
                    continue;
                }

                if ($startChanged && $endChanged) {
                    $totalBreakTime = $newStart->diffInMinutes($newEnd);

                    $existingBreak->update([
                        'break_start' => $newStart,
                        'break_end' => $newEnd,
                        'total_break_time' => $totalBreakTime,
                    ]);
                    $hasBreakUpdate = true;
                    continue;
                }
            }
        }
        if($hasAttendanceUpdate || $hasBreakUpdate){
            if ($hasBreakUpdate) {
                $attendance->load('breaks');
                $totalBreakMinutes = $attendance->breaks->sum('total_break_time');

                $updateClockIn = $updateData['clock_in'] ?? $attendance->clock_in;
                $updateClockOut = $updateData['clock_out'] ?? $attendance->clock_out;

                $totalWork = $updateClockIn->diffInMinutes($updateClockOut) - $totalBreakMinutes;

                $attendance->update([
                    'total_work_time' => $totalWork
                ]);
            }

            if ($revision) {
                $revision->update([
                    'status' => AttendanceRevision::STATUS_APPROVED,
                ]);
            }
        }
        return redirect()->back()->with('message','修正申請を承認しました。');
    }
}
