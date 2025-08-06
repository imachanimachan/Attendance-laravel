@extends('layout.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/index.css')}}">
@endsection

@section('content')
<div class="attendance__status">
    <span class="attendance__badge">{{ $attendance ? $attendance->status->name : '勤務外' }}</span>
</div>
@php
	use Carbon\Carbon;
	$now = Carbon::now()->locale('ja');
@endphp

<p class="attendance__date">{{ $now->isoFormat('YYYY年M月D日(ddd)') }}</p>
<p class="attendance__time">{{ $now->format('H:i') }}</p>

@if (!$attendance || $attendance->status->name === '勤務外')
<div class="attendance__form-wrapper">
    <form action="/attendance" method="POST" class="attendance__form">
    @csrf
        <button type="submit" class="attendance__action-button">出勤</button>
    </form>
</div>
@elseif ( $attendance->status->name === '出勤中')
<div class="attendance__form-group">
    <form action="/attendance" method="POST" class="attendance__form">
    @method('PATCH')
    @csrf
        <button type="submit" class="attendance__action-button">退勤</button>
    </form>
    <form action="/break" method="POST" class="attendance__form">
    @csrf
        <button type="submit" class="attendance__action-button attendance__action-button--white">休憩入</button>
    </form>
</div>
@elseif ( $attendance->status->name === '休憩中')
<div class="attendance__form-wrapper">
    <form action="/break" method="POST" class="attendance__form">
    @method('PATCH')
    @csrf
        <button type="submit" class="attendance__action-button attendance__action-button--white">休憩戻</button>
    </form>
</div>
@elseif ( $attendance->status->name === '退勤済')
<div class="attendance__message">
	<p>お疲れさまでした。</p>
</div>
@endif
@endsection