@extends('layout.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/approve.css')}}">
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
		<form action="{{ route( 'admin.approved' ,['id' => $attendance->id]) }}" method="POST">
				@method('PATCH')
				@csrf
			<table class="attendance-detail__table">
				<tr class="attendance-detail__row">
					<th class="attendance-detail__header">名前</th>
					<td class="attendance-detail__data">{{ $attendance->user->name }}</td>
				</tr>
				<tr class="attendance-detail__row">
					<th class="attendance-detail__header">日付</th>
					<td class="attendance-detail__data">
						{{ \Carbon\Carbon::parse($attendance->date)->format('Y年n月j日') }}
					</td>
				</tr>
				<tr class="attendance-detail__row">
					<th class="attendance-detail__header">出勤・退勤</th>
					<td class="attendance-detail__data">
						<div class="attendance-detail__time-row">
							<span class="attendance-detail__text">{{ \Carbon\Carbon::parse($clockIn)->format('H:i') }}</span>
							<input type="hidden" name="clockIn" value="{{ $clockIn }}">
							<span class="attendance-detail__separator">～</span>
							<span class="attendance-detail__text">{{ \Carbon\Carbon::parse($clockOut)->format('H:i') }}</span>
							<input type="hidden" name="clockOut" value="{{ $clockOut }}">

						</div>
					</td>
				</tr>
				@foreach ($breakDisplays as $breakDisplay)
				<tr class="attendance-detail__row">
					<th class="attendance-detail__header">休憩{{ $breakDisplay['display_order'] }}</th>
					<td class="attendance-detail__data">
						<div class="attendance-detail__time-row">
							<span class="attendance-detail__text">{{ $breakDisplay['start'] ? $breakDisplay['start']->format('H:i') : '' }}</span>
							<input type="hidden" name= "breaks[{{ $breakDisplay['display_order'] }}][start]" value="{{ $breakDisplay['start'] ?? '' }}">
							<span class="attendance-detail__separator">～</span>
							<span class="attendance-detail__text">{{ $breakDisplay['end'] ? $breakDisplay['end']->format('H:i') : '' }}</span>
							<input type="hidden" name= "breaks[{{ $breakDisplay['display_order'] }}][end]" value="{{ $breakDisplay['end'] ?? '' }}">
						</div>
					</td>
				</tr>
				@endforeach
				<tr class="attendance-detail__row">
					<th class="attendance-detail__header">備考</th>
					<td class="attendance-detail__data">
						<span class="attendance-detail__text">{{ $revision->note }}</span>
					</td>
				</tr>
			</table>
			<div class="attendance-detail__button-wrapper">
				<button type="submit" class="attendance-detail__button">承認</button>
			</div>
		</form>
	</div>
</div>
@endsection