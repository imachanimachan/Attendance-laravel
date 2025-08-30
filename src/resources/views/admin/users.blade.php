@extends('layout.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/users.css')}}">
@endsection

@section('content')
<div class="user-list">
	<div class="user-list__container">
		<h1 class="user-list__title">スタッフ一覧</h1>
		<table class="user-list__table">
				<tr class="user-list__header-row">
					<th class="user-list__header-cell">名前</th>
					<th class="user-list__header-cell">メールアドレス</th>
					<th class="user-list__header-cell">月次勤怠</th>
				</tr>
			<tbody class="user-list__tbody">
				@foreach ($users as $user)
					<tr class="user-list__row">
						<td class="user-list__cell">
							{{ $user->name }}
						</td>
						<td class="user-list__cell">
							{{ $user->email }}
						</td>
						<td class="user-list__cell">
							<a href="{{ route('admin.user.attendance', ['id' => $user->id]) }}" class="user-list__detail-link">詳細</a>
						</td>
					</tr>
				@endforeach
			</tbody>
		</table>
	</div>
</div>
@endsection
