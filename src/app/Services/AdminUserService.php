<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminUserService
{
    public function getUserMonthlyAttendance(int $userId, ?int $year = null, ?int $month = null): array
	{
		$user = User::find($userId);

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
			'user' => $user,
			'daysInMonth' => $daysInMonth,
			'displayYear' => $displayYear,
			'displayMonth' => $displayMonth,
			'prevYear' => $prevMonthDate->year,
			'prevMonth' => $prevMonthDate->month,
			'nextYear' => $nextMonthDate->year,
			'nextMonth' => $nextMonthDate->month,
		];
	}

    public function exportMonthlyAttendanceCsv(int $userId, int $year, int $month): StreamedResponse
	{
		$attendances = Attendance::with('breaks')
			->where('user_id', $userId)
			->whereYear('date', $year)
			->whereMonth('date', $month)
			->orderBy('date')
			->get();

		$user = User::find($userId);

		$headers = ['日付', '出勤', '退勤', '休憩', '合計'];

		$callback = function () use ($attendances, $headers, $year, $month, $user) {
			$output = fopen('php://output', 'w');
			fwrite($output, "\xEF\xBB\xBF");
			fwrite($output, "{$year}年{$month}月分{$user->name}さんの勤怠\n\n");
			fputcsv($output, $headers);

			$daysOfWeek = ['日', '月', '火', '水', '木', '金', '土'];

			foreach ($attendances as $attendance) {
				$clockIn = $attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : '';
				$clockOut = $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '';

				$breakMinutes = $attendance->breaks->sum('total_break_time');
				$breakDisplay = sprintf('%d:%02d', floor($breakMinutes / 60), $breakMinutes % 60);

				$workMinutes = $attendance->total_work_time ?? 0;
				$workDisplay = $workMinutes > 0 ? sprintf('%d:%02d', floor($workMinutes / 60), $workMinutes % 60) : '';

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
}
