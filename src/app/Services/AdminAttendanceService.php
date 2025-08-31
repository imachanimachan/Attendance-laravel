<?php

namespace App\Services;

use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use App\Models\AttendanceRevision;

class AdminAttendanceService
{
    public function getAttendanceData(array $query)
	{
		$year = $query['year'] ?? null;
		$month = $query['month'] ?? null;
		$day = $query['day'] ?? null;

		$currentDate = ($year && $month && $day)
			? Carbon::createFromDate($year, $month, $day)
			: Carbon::now();

		$user = Auth::user();

		$attendances = Attendance::with('breaks', 'user')
			->whereDate('date', $currentDate)
			->get();

		$prevDate = $currentDate->copy()->subDay();
		$nextDate = $currentDate->copy()->addDay();

		return [
			'attendances' => $attendances,
			'displayYear' => $currentDate->year,
			'displayMonth' => $currentDate->month,
			'displayDate' => $currentDate->day,
			'prevYear' => $prevDate->year,
			'prevMonth' => $prevDate->month,
			'prevDay' => $prevDate->day,
			'nextYear' => $nextDate->year,
			'nextMonth' => $nextDate->month,
			'nextDay' => $nextDate->day,
		];
	}

    public function getAttendanceDetail(int $id)
	{
		$attendance = Attendance::with(['breaks', 'user'])->find($id);

		$attendanceRevision = AttendanceRevision::where('attendance_id', $attendance->id)->first();

		return [
			'attendance' => $attendance,
			'attendanceRevision' => $attendanceRevision,
		];
	}
}
