<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\Status;
use Illuminate\Support\Carbon;
use App\Models\RestBreak;
use App\Models\BreakRevision;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\AttendanceRevisionRequest;
use App\Models\AttendanceRevision;

class AttendanceService
{
    public function getTodayAttendance()
    {
        $user = Auth::user();

        return Attendance::where('user_id', $user->id)
            ->whereDate('date', today())
            ->with('status')
            ->first();
    }

    public function startAttendance($user)
    {
        $status = Status::where('name', '出勤中')->first();

        return Attendance::create([
            'user_id' => $user->id,
            'status_id' => $status->id,
            'date' => today(),
            'clock_in' => Carbon::now(),
        ]);
    }

    public function startBreak()
    {
        $user = Auth::user();
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', today())
            ->first();

        $breakCount = RestBreak::where('attendance_id', $attendance->id)->count();

        $displayOrder = $breakCount + 1;

        RestBreak::create([
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::now(),
            'display_order' => $displayOrder,
        ]);

        $status = Status::where('name', '休憩中')->first();

        $attendance->update([
            'status_id' => $status->id,
        ]);
    }

    public function endBreak()
    {
        $user = Auth::user();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', today())
            ->firstOrFail();

        $break = RestBreak::where('attendance_id', $attendance->id)
            ->whereNull('break_end')
            ->latest('break_start')
            ->firstOrFail();

        $breakEnd = Carbon::now();
        $totalBreakTime = floor($break->break_start->diffInSeconds($breakEnd) / 60);

        $break->update([
            'break_end' => $breakEnd,
            'total_break_time' => $totalBreakTime,
        ]);

        $status = Status::where('name', '出勤中')->firstOrFail();
        $attendance->update([
            'status_id' => $status->id,
        ]);

        return true;
    }

    public function endAttendance()
    {
        $user = Auth::user();
        $status = Status::where('name', '退勤済')->first();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', today())
            ->first();

        $clockOut = Carbon::now();

        $rawWorkSeconds = $attendance->clock_in->diffInSeconds($clockOut);

        $totalBreakSeconds = $attendance->breaks
            ->whereNotNull('break_end')
            ->sum(function ($break) {
                return $break->break_start->diffInSeconds($break->break_end);
            });

        $totalWorkMinutes = floor(($rawWorkSeconds - $totalBreakSeconds) / 60);

        $attendance->update([
            'status_id' => $status->id,
            'clock_out' => $clockOut,
            'total_work_time' => $totalWorkMinutes,
        ]);
    }

    public function getMonthlyAttendances($user, $year = null, $month = null)
    {
        $year = $year ?? date('Y');
        $month = $month ?? date('m');

        $displayYear = (int)$year;
        $displayMonth = (int)$month;

        $startDate = Carbon::create($displayYear, $displayMonth, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth()->endOfDay();

        $attendancesRaw = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        $attendanceMap = $attendancesRaw->keyBy(fn($item) => Carbon::parse($item->date)->format('Y-m-d'));

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

        return [
            'daysInMonth' => $daysInMonth,
            'displayYear' => $displayYear,
            'displayMonth' => $displayMonth,
            'prevYear' => $prevMonthDate->year,
            'prevMonth' => $prevMonthDate->month,
            'nextYear' => $nextMonthDate->year,
            'nextMonth' => $nextMonthDate->month,
        ];
    }

    public function getAttendanceWithRevisionStatus(int $attendanceId): array
	{
		$attendance = Attendance::with(['breaks', 'user'])->find($attendanceId);

		$pendingRevisionExists = AttendanceRevision::where('attendance_id', $attendance->id)
			->where('status', AttendanceRevision::STATUS_PENDING)
			->exists();

		return [
			'attendance' => $attendance,
			'pendingRevisionExists' => $pendingRevisionExists,
		];
	}

	public function requestRevision(int $attendanceId, AttendanceRevisionRequest $request)
	{
		DB::beginTransaction();
		try {
			$attendance = Attendance::with('breaks')->find($attendanceId);

			$currentClockIn = Carbon::parse($attendance->clock_in);
			$currentClockOut = Carbon::parse($attendance->clock_out);

			$inputClockIn = $request->input('clock_in');
			$inputClockOut = $request->input('clock_out');

			$revisedClockIn = Carbon::parse($currentClockIn->format('Y-m-d').' '.$inputClockIn);
			$revisedClockOut = Carbon::parse($currentClockOut->format('Y-m-d').' '.$inputClockOut);

			$note = $request->input('note');

			$hasAttendanceChanged = !$currentClockIn->eq($revisedClockIn) || !$currentClockOut->eq($revisedClockOut);

			$revisionBreaks = $request->input('breaks', []);
			$hasBreakChanged = false;
			$breakRevisionsToSave = [];

			foreach ($revisionBreaks as $displayOrder => $revisionBreak) {
				$inputBreakStart = $revisionBreak['break_start'] ?? null;
				if ($inputBreakStart === '') $inputBreakStart = null;

				$inputBreakEnd = $revisionBreak['break_end'] ?? null;
				if ($inputBreakEnd === '') $inputBreakEnd = null;

				$revisedBreakStart = $inputBreakStart ? Carbon::parse($currentClockIn->format('Y-m-d').' '.$inputBreakStart) : null;
				$revisedBreakEnd = $inputBreakEnd ? Carbon::parse($currentClockIn->format('Y-m-d').' '.$inputBreakEnd) : null;

				$currentBreak = $attendance->breaks->firstWhere('display_order', $displayOrder);
				$currentBreakStart = optional($currentBreak)->break_start ? Carbon::parse($currentBreak->break_start) : null;
				$currentBreakEnd = optional($currentBreak)->break_end ? Carbon::parse($currentBreak->break_end) : null;
				$currentBreakId = optional($currentBreak)->id;

				$isChanged = !$this->isSameCarbon($currentBreakStart, $revisedBreakStart)
					|| !$this->isSameCarbon($currentBreakEnd, $revisedBreakEnd);

				if ($isChanged) {
					$hasBreakChanged = true;
					$breakRevisionsToSave[] = [
						'break_id' => $currentBreakId,
						'original_break_start' => $currentBreakStart,
						'original_break_end' => $currentBreakEnd,
						'revised_break_start' => $revisedBreakStart,
						'revised_break_end' => $revisedBreakEnd,
					];
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
					'status' => AttendanceRevision::STATUS_PENDING
				]);

				if ($hasBreakChanged) {
					foreach ($breakRevisionsToSave as $revision) {
						BreakRevision::create(array_merge($revision, [
							'attendance_revision_id' => $attendanceRevision->id,
						]));
					}
				}

				DB::commit();
				return ['status' => 'success', 'message' => '修正しました。'];
			} else {
				DB::rollBack();
				return ['status' => 'info', 'message' => '修正するデータがありません。'];
			}
		} catch (\Exception $e) {
			DB::rollBack();
			return ['status' => 'error', 'message' => '修正に失敗しました。'];
		}
	}

	private function isSameCarbon(?Carbon $a, ?Carbon $b): bool
	{
		if($a === null && $b === null) return true;
		if($a === null || $b === null) return false;
		return $a->eq($b);
	}

    public function getRequestList(User $user, ?string $tab)
	{
		$query = AttendanceRevision::with(['attendance.user'])
			->whereHas('attendance', function ($query) use ($user) {
				$query->where('user_id', $user->id);
			});

		if ($tab === 'approved') {
			$query->where('status', AttendanceRevision::STATUS_APPROVED);
		} else {
			$query->where('status', AttendanceRevision::STATUS_PENDING);
		}

		return $query->get();
	}
}
