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
						{{ \Carbon\Carbon::parse($attendance->date)->format('Y年　n月j日') }}
					</td>
				</tr>
				<tr class="attendance-detail__row">
					<th class="attendance-detail__header">出勤・退勤</th>
					<td class="attendance-detail__data">
						<div class="attendance-detail__time-row">
							<span class="attendance-detail__text">{{ \Carbon\Carbon::parse($attendanceRevision->revised_clock_in)->format('H:i') }}</span>
							<input type="hidden" name="clockIn" value="{{ $attendanceRevision->revised_clock_in }}">
							<span class="attendance-detail__separator">～</span>
							<span class="attendance-detail__text">{{ \Carbon\Carbon::parse($attendanceRevision->revised_clock_out)->format('H:i') }}</span>
							<input type="hidden" name="clockOut" value="{{ $attendanceRevision->revised_clock_out }}">

						</div>
					</td>
				</tr>
				@foreach ($mergedBreaks as $break)
				<tr class="attendance-detail__row">
					<th class="attendance-detail__header">休憩{{ $break['display_order'] }}</th>
					<td class="attendance-detail__data">
						<div class="attendance-detail__time-row">
							<span class="attendance-detail__text">{{ $break['start'] ? \Carbon\Carbon::parse($break['start'])->format('H:i') : '' }}</span>
							<input type="hidden" name="breaks[{{ $break['display_order'] }}][start]" value="{{ $break['start'] ?? '' }}">
							<span class="attendance-detail__separator">～</span>
							<span class="attendance-detail__text">{{ $break['end'] ? \Carbon\Carbon::parse($break['end'])->format('H:i') : '' }}</span>
							<input type="hidden" name="breaks[{{ $break['display_order'] }}][end]" value="{{ $break['end'] ?? '' }}">
						</div>
					</td>
				</tr>
				@endforeach
				<tr class="attendance-detail__row--note">
					<th class="attendance-detail__header">備考</th>
					<td class="attendance-detail__data">
						<span class="attendance-detail__text">{{ $attendanceRevision->note }}</span>
					</td>
				</tr>
			</table>
			@if($attendanceRevision->isPending())
			<div class="attendance-detail__button-wrapper">
				<button type="submit" class="attendance-detail__button">承認</button>
			</div>
			@else
			<div class="attendance-detail__button-wrapper">
				<button type="button" class="attendance-detail__button--disabled">承認済み</button>
			</div>
			@endif
		</form>
	</div>
</div>
@endsection