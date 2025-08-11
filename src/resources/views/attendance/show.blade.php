@extends('layout.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/show.css')}}">
@endsection

@section('content')
<div class="attendance-detail">
	<div class="attendance-detail__container">
	@if (session('message'))
    <div class="flash-message">
        {{ session('message') }}
    </div>
    @endif
		<h1 class="attendance-detail__title">勤怠詳細</h1>
		<form action="{{ route( 'attendance.request' ,['id' => $attendance->id]) }}" method="POST">
				@csrf
			<table class="attendance-detail__table">
				<tr class="attendance-detail__row">
					<th class="attendance-detail__header">名前</th>
					<td class="attendance-detail__data">{{ $attendance->user->name }}</td>
				</tr>
				<tr class="attendance-detail__row">
					<th class="attendance-detail__header">日付</th>
					<td class="attendance-detail__data">
						{{ \Carbon\Carbon::parse($attendance->date)->format('Y年　n月j日') }}
					</td>
				</tr>
				@if ($pendingRevisionExists)
				<tr class="attendance-detail__row">
					<th class="attendance-detail__header">出勤・退勤</th>
					<td class="attendance-detail__data">
						<div class="attendance-detail__time-row">
							<span class="attendance-detail__text">{{ $attendance->clock_in->format('H:i') }}</span>
							<span class="attendance-detail__separator">～</span>
							<span class="attendance-detail__text">{{ $attendance->clock_out->format('H:i') }}</span>
						</div>
					</td>
				</tr>
				@foreach ($attendance->breaks->sortBy('display_order') as $break)
				<tr class="attendance-detail__row">
					<th class="attendance-detail__header">休憩{{ $break->display_order }}</th>
					<td class="attendance-detail__data">
						<div class="attendance-detail__time-row">
							<span class="attendance-detail__text">{{ $break->break_start->format('H:i') }}</span>
							<span class="attendance-detail__separator">～</span>
							<span class="attendance-detail__text">{{ $break->break_end->format('H:i') }}</span>
						</div>
					</td>
				</tr>
				@endforeach
				<tr class="attendance-detail__row">
					<th class="attendance-detail__header">備考</th>
					<td class="attendance-detail__data">
						<span class="attendance-detail__text">{{ $attendance->note }}</span>
					</td>
				</tr>
				@else
				<tr class="attendance-detail__row">
					<th class="attendance-detail__header">出勤・退勤</th>
					<td class="attendance-detail__data">
						<input type="text" name="clock_in" class="attendance-detail__input" value="{{ old('clock_in', $attendance->clock_in->format('H:i')) }}">
						～
						<input type="text" name="clock_out" class="attendance-detail__input" value="{{ old('clock_out', $attendance->clock_out->format('H:i')) }}">
					</td>
				</tr>
				@foreach ($attendance->breaks->sortBy('display_order') as $break)
					<tr class="attendance-detail__row">
						<th class="attendance-detail__header">休憩{{ $break->display_order }}</th>
						<td class="attendance-detail__data">
							<input type="text" name="breaks[{{ $break->display_order }}][break_start]" class="attendance-detail__input" value="{{ old('breaks.'.$break->display_order.'.break_start', optional($break->break_start)->format('H:i')) }}">
							～
							<input type="text" name="breaks[{{ $break->display_order }}][break_end]" class="attendance-detail__input" value="{{ old('breaks.'.$break->display_order.'.break_end', optional($break->break_end)->format('H:i')) }}">
						</td>
					</tr>
				@endforeach
				@php
				$nextDisplayOrder = ($attendance->breaks->max('display_order') ?? 0) + 1;
				@endphp
				<tr class="attendance-detail__row">
					<th class="attendance-detail__header">休憩{{ $nextDisplayOrder }}</th>
					<td class="attendance-detail__data">
						<input type="text" name="breaks[{{ $nextDisplayOrder }}][break_start]" class="attendance-detail__input" value="{{ old('breaks.'.$nextDisplayOrder.'.break_start') }}">
						～
						<input type="text" name="breaks[{{ $nextDisplayOrder }}][break_end]" class="attendance-detail__input" value="{{ old('breaks.'.$nextDisplayOrder.'.break_end') }}">
					</td>
				</tr>
				<tr class="attendance-detail__row">
					<th class="attendance-detail__header">備考</th>
					<td class="attendance-detail__data"><input type="text" name="note" class="attendance-detail__input attendance-detail__input--note" value="{{ old('attendance->note') }}"></td>
				</tr>
				@endif
			</table>
			<div class="attendance-detail__button-wrapper">
				@if ($pendingRevisionExists)
				<div class="flash-message">*承認待ちのため修正はできません。</div>
				@else
				<button type="submit" class="attendance-detail__button">修正</button>
				@endif
			</div>
		</form>
	</div>
</div>
@endsection