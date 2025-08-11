@extends('layout.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/show.css')}}">
@endsection

@section('content')
<div class="attendance-detail">
	<div class="attendance-detail__container">
	@if (session('message'))
    <div class="flash-message">
        {{ session('message') }}
    </div>
    @endif
	@if($attendanceRevision && $attendanceRevision->isPending())
	<div class="alert alert--warning">
		<p class="alert__text">
			※この勤怠は修正申請があります。
			<a href="/admin/requests" class="alert__link">申請一覧ページ</a>より確認してください。
		</p>
	</div>
	@endif
		<h1 class="attendance-detail__title">勤怠詳細</h1>
		<form action="{{ route( 'admin.request' ,['id' => $attendance->id]) }}" method="POST">
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
				<tr class="attendance-detail__row">
					<th class="attendance-detail__header">出勤・退勤</th>
					<td class="attendance-detail__data">
						<input type="text" name="clock_in" class="attendance-detail__input" value="{{ old('clock_in', $attendance->clock_in->format('H:i')) }}">
						<span class="attendance-detail__separator">～</span>
						<input type="text" name="clock_out" class="attendance-detail__input" value="{{ old('clock_out', $attendance->clock_out->format('H:i')) }}">
					</td>
				</tr>
				@foreach ($attendance->breaks->sortBy('display_order') as $break)
					<tr class="attendance-detail__row">
						<th class="attendance-detail__header">休憩{{ $break->display_order }}</th>
						<td class="attendance-detail__data">
							<input type="text" name="breaks[{{ $break->display_order }}][break_start]" class="attendance-detail__input" value="{{ old('breaks.'.$break->display_order.'.break_start', optional($break->break_start)->format('H:i')) }}">
							<span class="attendance-detail__separator">～</span>
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
						<span class="attendance-detail__separator">～</span>
						<input type="text" name="breaks[{{ $nextDisplayOrder }}][break_end]" class="attendance-detail__input" value="{{ old('breaks.'.$nextDisplayOrder.'.break_end') }}">
					</td>
				</tr>
				<tr class="attendance-detail__row--note">
					<th class="attendance-detail__header">備考</th>
					<td class="attendance-detail__data"><input type="text" name="note" class="attendance-detail__input attendance-detail__input--note" value="{{ old('attendance->note') }}"></td>
				</tr>
			</table>
			<div class="attendance-detail__button-wrapper">
				<button type="submit" class="attendance-detail__button">修正</button>
			</div>
		</form>
	</div>
</div>
@endsection