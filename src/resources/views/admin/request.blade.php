@extends('layout.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/request.css')}}">
@endsection

@section('content')
	<div class="attendance-request__container">
		<h1 class="attendance-request__title">申請一覧</h1>

		<div class="attendance-request__tabs">
			<a href="{{ route( 'admin.request.list', ['tab' => 'pending']) }}" class="attendance-request__tab-link {{ request('tab', 'pending') === 'pending' ? 'attendance-request__tab-link--active' : '' }}">承認待ち</a>
			<a href="{{ route( 'admin.request.list', ['tab' => 'approved']) }}" class="attendance-request__tab-link {{ request('tab', 'approved') === 'approved' ? 'attendance-request__tab-link--active' : '' }}">承認済み</a>
		</div>

		<div class="attendance-request__table-wrapper">
			<table class="attendance-request__table">
				<thead class="attendance-request__thead">
					<tr class="attendance-request__header-row">
						<th class="attendance-request__header">状態</th>
						<th class="attendance-request__header">名前</th>
						<th class="attendance-request__header">対象日付</th>
						<th class="attendance-request__header">申請理由</th>
						<th class="attendance-request__header">申請日時</th>
						<th class="attendance-request__header">詳細</th>
					</tr>
				</thead>
				<tbody class="attendance-request__tbody">
					@foreach ($attendanceRevisions as $attendanceRevision)
					<tr class="attendance-request__row">
						<td class="attendance-request__cell">{{ $attendanceRevision->status_label }}</td>
						<td class="attendance-request__cell">{{ $attendanceRevision->attendance->user->name }}</td>
						<td class="attendance-request__cell">{{ \Carbon\Carbon::parse($attendanceRevision->attendance->date)->format('Y/m/d') }}</td>
						<td class="attendance-request__cell">{{ $attendanceRevision->note }}</td>
						<td class="attendance-request__cell">{{ \Carbon\Carbon::parse($attendanceRevision->applied_on)->format('Y/m/d') }}</td>
						<td class="attendance-request__cell">
							<a href="{{ route( 'admin.request.show',        ['attendance_correct_request' => $attendanceRevision->attendance->id]) }}" class="attendance-request__link">詳細</a>
						</td>
					</tr>
					@endforeach
				</tbody>
			</table>
		</div>
</div>
@endsection
