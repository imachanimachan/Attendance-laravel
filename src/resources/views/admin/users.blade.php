@extends('layout.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/users.css')}}">
@endsection

@section('content')
<div class="attendance-list">
	<div class="attendance-list__container">
		<h1 class="attendance-list__title">スタッフ一覧</h1>
        <table class="attendance-list__table">
			<thead class="attendance-list__thead">
				<tr class="attendance-list__header-row">
					<th class="attendance-list__header-cell">名前</th>
					<th class="attendance-list__header-cell">メールアドレス</th>
					<th class="attendance-list__header-cell">月次勤怠</th>
				</tr>
			</thead>
			<tbody class="attendance-list__tbody">
				@foreach ($users as $user)
					<tr class="attendance-list__row">
                    <td class="attendance-list__cell">
                        {{ $user->name }}
                    </td>
                    <td class="attendance-list__cell">
                    {{ $user->email }}
                    </td>
					<td class="attendance-list__cell">
                        <a href="{{ route( 'admin.users.attendances' ,['user' => $user->id]) }}"class="attendance-list__detail-link">詳細</a>
                    </td>
					</tr>
				@endforeach
			</tbody>
		</table>
	</div>
</div>
@endsection
