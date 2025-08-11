<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Illuminate\Support\Carbon;
use App\Models\RestBreak;
use App\Models\User;
use App\Models\AttendanceRevision;
use App\Models\BreakRevision;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->query('year');
        $month = $request->query('month');
        $day = $request->query('day');

        if ($year && $month && $day) {
            $currentDate = Carbon::createFromDate($year, $month, $day);
        } else {
            $currentDate = Carbon::now();
        }

        $user = Auth::user();

        $attendances = Attendance::with('breaks' ,'user')
            ->whereDate('date', $currentDate)
            ->get();

        $prevDate = $currentDate->copy()->subDay();
        $nextDate = $currentDate->copy()->addDay();

        $displayYear = $currentDate->year;
        $displayMonth = $currentDate->month;
        $displayDate = $currentDate->day;

        $prevYear = $prevDate->year;
        $prevMonth = $prevDate->month;
        $prevDay = $prevDate->day;

        $nextYear = $nextDate->year;
        $nextMonth = $nextDate->month;
        $nextDay = $nextDate->day;


    return view('admin.list', compact(
            'attendances',
            'displayYear',
            'displayMonth',
            'displayDate',
            'prevYear',
            'prevMonth',
            'prevDay',
            'nextYear',
            'nextMonth',
            'nextDay'
        ));
    }

    public function show($id, Request $request)
    {
        $attendance = Attendance::with(['breaks', 'user'])->find($id);

        $attendanceRevision = AttendanceRevision::where('attendance_id', $attendance->id)->first();

        return view('admin.show', compact('attendance', 'attendanceRevision'));
    }

    public function request($id, Request $request)
    {

        DB::beginTransaction();
        try{
            $attendance = Attendance::with('breaks')->find($id);

            $currentClockIn = Carbon::parse($attendance->clock_in);
            $currentClockOut = Carbon::parse($attendance->clock_out);

            $inputClockIn = $request->input('clock_in');
            $inputClockOut = $request->input('clock_out');

            $revisedClockIn = Carbon::parse($currentClockIn->format('Y-m-d').' '.$inputClockIn);
            $revisedClockOut = Carbon::parse($currentClockOut->format('Y-m-d').' '. $inputClockOut);

            $note = $request->input('note');

            $hasAttendanceChanged =
                !$currentClockIn->eq($revisedClockIn) ||
                !$currentClockOut->eq($revisedClockOut);

            $revisionBreaks = $request->input('breaks', []);
            $hasBreakChanged = false;
            $breakRevisionsToSave = [];
            $breakChanges = [];

            foreach ($revisionBreaks as $displayOrder => $revisionBreak) {
                    $inputBreakStart = $revisionBreak['break_start'] ?? null;
                    if($inputBreakStart === ''){
                        $inputBreakStart = null;
                    }

                    $inputBreakEnd = $revisionBreak['break_end'] ?? null;
                    if($inputBreakEnd === ''){
                        $inputBreakEnd = null;
                    }

                    $revisedBreakStart = $inputBreakStart !== null ? Carbon::parse($currentClockIn->format('Y-m-d').' '.$inputBreakStart) : null;
                    $revisedBreakEnd = $inputBreakEnd !== null ? Carbon::parse($currentClockIn->format('Y-m-d').' '.$inputBreakEnd) : null;

                    $currentBreak = $attendance->breaks->firstWhere('display_order', $displayOrder);

                    $originalBreakStart = optional($currentBreak)->break_start;
                    $originalBreakEnd = optional($currentBreak)->break_end;
                    $currentBreakId = optional($currentBreak)->id;

                    $currentBreakStart = $originalBreakStart ? Carbon::parse($originalBreakStart) : null;
                    $currentBreakEnd = $originalBreakEnd ? Carbon::parse($originalBreakEnd) : null;

                    $isChanged =
                            !$this->isSameCarbon($currentBreakStart, $revisedBreakStart) ||
                            !$this->isSameCarbon($currentBreakEnd, $revisedBreakEnd);

                    if ($isChanged) {
                        $hasBreakChanged = true;

                        $breakRevisionsToSave[] = [
                            'break_id' => $currentBreakId,
                            'original_break_start' => $currentBreakStart,
                            'original_break_end' => $currentBreakEnd,
                            'revised_break_start' => $revisedBreakStart,
                            'revised_break_end' => $revisedBreakEnd,
                        ];

                        if(is_null($revisedBreakStart) && is_null($revisedBreakEnd)){
                            if($currentBreak){
                                $breakChanges[] = [
                                    'type' => 'delete',
                                    'break' => $currentBreak
                                ];
                            }
                            continue;
                        }

                        if(!$currentBreak){
                            $breakChanges[] = [
                                'type' => 'create',
                                'start' => $revisedBreakStart,
                                'end' => $revisedBreakEnd,
                                'order' => $displayOrder,
                            ];
                        }else{
                                $breakChanges[] = [
                                'type' => 'update',
                                'start' => $revisedBreakStart,
                                'end' => $revisedBreakEnd,
                                'order' => $displayOrder,
                                'break' => $currentBreak
                            ];
                        }
                    }
                }

            if ($hasAttendanceChanged || $hasBreakChanged) {
            $attendanceRevision = AttendanceRevision::create([
                'attendance_id' => $attendance->id,
                'applied_on' => now(),
                'original_clock_in' => $currentClockIn,
                'original_clock_out' => $currentClockOut,
                'revised_clock_in' => $revisedClockIn,
                'revised_clock_out' => $revisedClockOut,
                'note' => $note,
                'status' => AttendanceRevision::STATUS_APPROVED
                ]);

                if($hasAttendanceChanged){
                    $totalWorkTime = $revisedClockIn->diffInMinutes($revisedClockOut);
                    $attendance->update([
                        'clock_in' => $revisedClockIn,
                        'clock_out' => $revisedClockOut,
                        'total_work_time' => $totalWorkTime
                    ]);
                }

                if($hasBreakChanged){
                    foreach ($breakRevisionsToSave as $revision) {
                        BreakRevision::create(array_merge($revision, [
                            'attendance_revision_id' => $attendanceRevision->id,
                        ]));
                    }
                }

                foreach($breakChanges as $change){
                    if($change['type'] === 'delete'){
                        $change['break']->delete();
                    }elseif($change['type'] === 'create'){
                        $total = $change['start']->diffInMinutes($change['end']);
                        RestBreak::create([
                            'attendance_id' => $attendance->id,
                            'break_start' => $change['start'],
                            'break_end' => $change['end'],
                            'total_break_time' => $total,
                            'display_order' => $change['order'],
                        ]);
                    }elseif($change['type'] === 'update'){
                        $total = $change['start']->diffInMinutes($change['end']);
                        $change['break']->update([
                            'break_start' => $change['start'],
                            'break_end' => $change['end'],
                            'total_break_time' => $total,
                        ]);
                    }
                }

            }else{
                return redirect()->back()->with('message', '修正するデータがありません。')->withInput();;
            }
            DB::commit();

            return redirect()->back()->with('message', '修正しました。');

        }catch(\Exception $e){
        DB::rollBack();

        return redirect()->back()->with('message', '修正に失敗しました。')->withInput();;
        }
    }

    private function isSameCarbon(?Carbon $a, ?Carbon $b): bool {
                    if($a === null && $b === null) return true;
                    if($a === null || $b === null) return false;
                    return $a->eq($b);
                }

    public function listUsers()
    {
        $users = User::all();
        return view('admin.users', compact('users'));
    }

    public function showUserAttendances(Request $request, $id)
    {
        $user = User::find($id);

        $year = $request->input('year') ?? date('Y');
        $month = $request->input('month') ?? date('m');

        $displayYear = (int)$year;
        $displayMonth = (int)$month;

        $startDate = Carbon::create($displayYear, $displayMonth, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth()->endOfDay();

        // 勤怠データを取得
        $attendancesRaw = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        // 勤怠を日付でマップ（キー：日付のY-m-d形式）
        $attendanceMap = $attendancesRaw->keyBy(function ($item) {
            return Carbon::parse($item->date)->format('Y-m-d');
        });

        // 月の全日付を作成
        $daysInMonth = [];
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $dateKey = $current->format('Y-m-d');
            $daysInMonth[] = [
                'date' => $current->copy(),
                'attendance' => $attendanceMap[$dateKey] ?? null,
            ];
            $current->addDay();
        }

        $prevMonthDate = $startDate->copy()->subMonth();
        $nextMonthDate = $startDate->copy()->addMonth();

        $prevYear = $prevMonthDate->year;
        $prevMonth = $prevMonthDate->month;

        $nextYear = $nextMonthDate->year;
        $nextMonth = $nextMonthDate->month;

        return view('admin.user_attendance', compact(
            'user',
            'daysInMonth', // ← 変更点：これがviewで使う配列
            'displayYear',
            'displayMonth',
            'prevYear',
            'prevMonth',
            'nextYear',
            'nextMonth'
        ));
    }
        public function export(Request $request) :StreamedResponse
    {
        $request->validate([
            'user_id' => ['required', 'integer'],
            'year' => ['required', 'integer'],
            'month' => ['required', 'integer'],
        ]);

        $userId = $request->input('user_id');
        $year = $request->input('year');
        $month = $request->input('month');

        $attendances = Attendance::with('breaks')
            ->where('user_id', $userId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date')
            ->get();

        $user = User::find($userId);

        $headers = [
            '日付', '出勤', '退勤', '休憩', '合計'
        ];

        $callback = function () use ($attendances, $headers, $year, $month, $user) {
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        fwrite($output, "{$year}年{$month}月分{$user->name}さんの勤怠\n");
        fwrite($output, "\n");
        fputcsv($output, $headers);

        foreach ($attendances as $attendance) {
            $clockIn = $attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : '';
            $clockOut = $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '';

            $breakMinutes = $attendance->breaks->sum('total_break_time');
            $breakDisplay = sprintf('%d:%02d', floor($breakMinutes / 60), $breakMinutes % 60);

            $workMinutes = $attendance->total_work_time ?? 0;
            $workDisplay = $workMinutes > 0 ? sprintf('%d:%02d', floor($workMinutes / 60), $workMinutes % 60) : '';

            $daysOfWeek = ['日', '月', '火', '水', '木', '金', '土'];
            $date = Carbon::parse($attendance->date);
            $formattedDate = $date->format('Y/m/d') . '（' . $daysOfWeek[$date->dayOfWeek] . '）';

            fputcsv($output, [
                "\t" . $formattedDate,
                $clockIn,
                $clockOut,
                $breakDisplay,
                $workDisplay,
            ]);
        }

        fclose($output);
    };


        $filename = "attendance_{$userId}_{$year}_{$month}.csv";

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }


    public function requestList(request $request)
    {
        $tab = $request->query('tab');

        $query = AttendanceRevision::with(['attendance.user']);

        if ($tab === 'approved') {
            $query->where('status', AttendanceRevision::STATUS_APPROVED);
        } else {
            $query->where('status', AttendanceRevision::STATUS_PENDING);
        }

        $attendanceRevisions = $query->get();

        return view('admin.request', compact('attendanceRevisions'));

    }

    public function requestShow($id)
    {
        $attendance = Attendance::with('breaks')->findOrFail($id);

        $attendanceRevision = AttendanceRevision::with(['breakRevisions.break'])->where('attendance_id', $id)->first();

        $originalBreaks = $attendance->breaks->sortBy('display_order');

        $revisionsByBreakId = $attendanceRevision?->breakRevisions
            ->filter(fn($r) => $r->break_id !== null)
            ->keyBy('break_id');

        $additionalRevisions = $attendanceRevision?->breakRevisions
            ->filter(fn($r) => $r->break_id === null)
            ->values();

        $mergedBreaks = [];

        foreach ($originalBreaks as $break) {
            $revision = $revisionsByBreakId[$break->id] ?? null;

            if ($revision) {
                $mergedBreaks[] = [
                    'display_order' => $break->display_order,
                    'start' => $revision->revised_break_start,
                    'end' => $revision->revised_break_end,
                ];
            } else {
                $mergedBreaks[] = [
                    'display_order' => $break->display_order,
                    'start' => $break->break_start,
                    'end' => $break->break_end,
                ];
            }
        }

        $nextDisplayOrder = $originalBreaks->max('display_order') ?? 0;
        foreach ($additionalRevisions as $revision) {
            $nextDisplayOrder++;
            $mergedBreaks[] = [
                'display_order' => $nextDisplayOrder,
                'start' => $revision->revised_break_start,
                'end' => $revision->revised_break_end,
            ];
        }

        usort($mergedBreaks, fn($a, $b) => $a['display_order'] <=> $b['display_order']);

        return view('admin.approve', compact('attendanceRevision', 'attendance', 'mergedBreaks'));
    }

    public function approved($id, Request $request)
    {
        //クエリパラメータできた$attendance->idから検討の勤怠、それに紐づく休憩取ってくる
        //それを変数に入れてhiddenで送られてきた勤怠と休憩との差分がもしあれば更新。attendanceRivisionのstatusは2の承認済みにアップデート。休憩に関してはnullできたらdelete

        $attendance = Attendance::with('breaks')->find($id);

        $requestClockIn = Carbon::parse($request->input('clockIn'));
        $requestClockOut = Carbon::parse($request->input('clockOut'));

        $currentClockIn = Carbon::parse($attendance->clock_in);
        $currentClockOut = Carbon::parse($attendance->clock_out);

        $updateData = [];

        if(!$currentClockIn->eq($requestClockIn)){
            $updateData['clock_in'] = $requestClockIn;
        }
        if(!$currentClockOut->eq($requestClockOut)){
            $updateData['clock_out'] = $requestClockOut;
        }

        if(!empty($updateData)){
            $updateClockIn = $updateData['clock_in'] ?? $currentClockIn;
            $updateClockOut = $updateData['clock_out'] ?? $currentClockOut;
            $updateData['total_work_time'] = $updateClockIn->diffInMinutes($updateClockOut);

            $attendance->update($updateData);
        }

        $revision = AttendanceRevision::where('attendance_id', $attendance->id)->first();
        $hasAttendanceUpdate = (!empty($updateData));
        $hasBreakUpdate = false;

        //休憩hiddenで送った値を取得
        $breakInput = $request->input('breaks',[]);
        //元の休憩データをキーをdisplay_orderにして取得
        $currentBreaks = $attendance->breaks->keyBy('display_order');
        //hiddenで送った値をキーと値（休憩1とスタート、エンドみたいにして）値の数だけ繰り返して１つずつ取得。
        //さらにスタートとエンドに分ける
        foreach($breakInput as $order => $input){
            $inputStart = $input['start'] ?? null;
            $inputEnd = $input['end'] ?? null;

            //＄inputStartがあるなら、Carbonに直して使うなければnullを使う
            $newStart = $inputStart ? Carbon::parse($inputStart) : null;
            $newEnd = $inputEnd ? Carbon::parse($inputEnd) : null;
            //元の休憩データから$order（例：休憩1）と同じdisplay_orderをgetで取得。
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
             //eqで厳密比較して違えばtrue。
            //つまり、「既存の start と、フォームから来た start を比較して違ってたら true」
            $startChanged = !$existingBreak->break_start->eq($newStart);
            $endChanged = !$existingBreak->break_end->eq($newEnd);

                if ($startChanged && !$endChanged) {
                    // 開始のみ変更
                    $totalBreakTime = $newStart->diffInMinutes($existingBreak->break_end);

                    $existingBreak->update([
                        'break_start' => $newStart,
                        'total_break_time' => $totalBreakTime,
                    ]);
                    $hasBreakUpdate = true;
                    continue;
                }

                if (!$startChanged && $endChanged) {
                    // 終了のみ変更
                    $totalBreakTime = $existingBreak->break_start->diffInMinutes($newEnd);

                    $existingBreak->update([
                        'break_end' => $newEnd,
                        'total_break_time' => $totalBreakTime,
                    ]);
                    $hasBreakUpdate = true;
                    continue;
                }

                if ($startChanged && $endChanged) {
                    // 両方変更
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
            $revision->update([
                'status' =>AttendanceRevision::STATUS_APPROVED,
            ]);
        }

        return redirect()->back()->with('message','修正申請を承認しました。');
    }
}
