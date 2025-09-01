@extends('layout.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/user_attendance.css')}}">
@endsection

@section('content')
<div class="attendance-list">
	<div class="attendance-list__container">
		<h1 class="attendance-list__title">{{ $user->name }}さんの勤怠</h1>

		<div class="attendance-list__header">
			<a href="{{ route('admin.user.attendance', ['id' => $user->id, 'year' => $prevYear, 'month' => $prevMonth]) }}" class="attendance-list__month-button">← 前月</a>
			<div class="attendance-list__current-month">
				<span class="attendance-list__calendar-icon">📅</span>
				<span class="attendance-list__month-text">{{ $displayYear }}/{{ str_pad($displayMonth, 2, '0', STR_PAD_LEFT) }}</span>
			</div>
			<a href="{{ route('admin.user.attendance', ['id' => $user->id, 'year' => $nextYear, 'month' => $nextMonth]) }}" class="attendance-list__month-button">翌月 →</a>
		</div>

		<table class="attendance-list__table">
			<thead class="attendance-list__thead">
				<tr class="attendance-list__header-row">
					<th class="attendance-list__header-cell">日付</th>
					<th class="attendance-list__header-cell">出勤</th>
					<th class="attendance-list__header-cell">退勤</th>
					<th class="attendance-list__header-cell">休憩</th>
					<th class="attendance-list__header-cell">合計</th>
					<th class="attendance-list__header-cell">詳細</th>
				</tr>
			</thead>
			<tbody class="attendance-list__tbody">
				@php
					$weekdays = ['日', '月', '火', '水', '木', '金', '土'];
				@endphp
				@foreach ($daysInMonth as $day)
					@php
						$date = $day['date'];
						$attendance = $day['attendance'];
					@endphp
					<tr class="attendance-list__row">
						<td class="attendance-list__cell">
							{{ $date->format('m/d') }}({{ $weekdays[$date->dayOfWeek] }})
						</td>
						<td class="attendance-list__cell">
							{{ $attendance && $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '' }}
						</td>
						<td class="attendance-list__cell">
							{{ $attendance && $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}
						</td>
						@php
							$totalMinutesRaw = $attendance ? $attendance->breaks->sum('total_break_time') : 0;
							$totalHours = floor($totalMinutesRaw / 60);
							$totalMinutes = str_pad($totalMinutesRaw % 60, 2, '0', STR_PAD_LEFT);
							$breakDisplay = $attendance ? "{$totalHours}:{$totalMinutes}" : '';
						@endphp
						<td class="attendance-list__cell">{{ $breakDisplay }}</td>
						@php
							$totalWorkMinutes = $attendance ? $attendance->total_work_time : 0;
							$workHours = floor($totalWorkMinutes / 60);
							$workMinutes = str_pad($totalWorkMinutes % 60, 2, '0', STR_PAD_LEFT);
							$workDisplay = ($attendance && $totalWorkMinutes > 0) ? "{$workHours}:{$workMinutes}" : '';
						@endphp
						<td class="attendance-list__cell">{{ $workDisplay }}</td>
						<td class="attendance-list__cell">
							@if ($attendance)
								<a href="{{ route('admin.show', ['id' => $attendance->id]) }}" class="attendance-list__detail-link">詳細</a>
							@else
								<div>詳細</div>
							@endif
						</td>
					</tr>
				@endforeach
			</tbody>
		</table>

		<div class="export-form">
			<form action="{{ route('admin.attendance.export', ['user_id' => $user->id, 'year' => $displayYear, 'month' => $displayMonth]) }}" method="post">
				@csrf
				<input class="export-btn" type="submit" value="CSV出力">
			</form>
		</div>
	</div>
</div>
@endsection
