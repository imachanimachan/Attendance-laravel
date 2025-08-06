@extends('layout.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/list.css')}}">
@endsection

@section('content')
<div class="attendance-list">
	<div class="attendance-list__container">
		<h1 class="attendance-list__title">{{ $displayYear }}年{{ str_pad($displayMonth, 2, '0', STR_PAD_LEFT) }}月{{ $displayDate }}日の勤怠</h1>

<div class="attendance-list__header">
	<a href="{{ route('admin.list', ['year' => $prevYear, 'month' => $prevMonth, 'day' => $prevDay]) }}" class="attendance-list__month-button">← 前日</a>
	<div class="attendance-list__current-month">
		<span class="attendance-list__calendar-icon">📅</span>
		<span class="attendance-list__month-text">{{ $displayYear }}/{{ str_pad($displayMonth, 2, '0', STR_PAD_LEFT) }}/{{ $displayDate }}</span>
	</div>
    <a href="{{ route('admin.list', ['year' => $nextYear, 'month' => $nextMonth, 'day' => $nextDay]) }}" class="attendance-list__month-button">翌日 →</a>
</div>

		<table class="attendance-list__table">
			<thead class="attendance-list__thead">
				<tr class="attendance-list__header-row">
					<th class="attendance-list__header-cell">名前</th>
					<th class="attendance-list__header-cell">出勤</th>
					<th class="attendance-list__header-cell">退勤</th>
					<th class="attendance-list__header-cell">休憩</th>
					<th class="attendance-list__header-cell">合計</th>
					<th class="attendance-list__header-cell">詳細</th>
				</tr>
			</thead>
			<tbody class="attendance-list__tbody">
				@foreach ($attendances as $attendance)
					<tr class="attendance-list__row">
                    <td class="attendance-list__cell">
                        {{ $attendance->user->name }}
                    </td>
                    <td class="attendance-list__cell">
                            {{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '' }}
                        </td>
                        <td class="attendance-list__cell">
                            {{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}
                        </td>
                        @php
							$totalMinutesRaw = $attendance->breaks->sum('total_break_time') ?? 0;
							$totalHours = floor($totalMinutesRaw / 60);
							$totalMinutes = str_pad($totalMinutesRaw % 60, 2, '0', STR_PAD_LEFT);
							$breakDisplay = "{$totalHours}:{$totalMinutes}";
						@endphp
                        <td class="attendance-list__cell">{{ $breakDisplay }}</td>
                        @php
                            $totalHours = floor($attendance->total_work_time / 60);
                            $totalMinutes = str_pad($attendance->total_work_time % 60, 2, '0', STR_PAD_LEFT);
                            $totalDisplay = ($attendance->total_work_time > 0) ? "{$totalHours}:{$totalMinutes}" : '';
                        @endphp
                        <td class="attendance-list__cell">{{ $totalDisplay }}</td>
						<td class="attendance-list__cell"><a href="{{ route( 'admin.show' ,['id' => $attendance->id]) }}" class="attendance-list__detail-link">詳細</a></td>
					</tr>
				@endforeach
			</tbody>
		</table>
	</div>
</div>
@endsection
